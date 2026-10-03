<?php

namespace App\Http\Controllers;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Http\Request;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function bookings(Request $request): StreamedResponse
    {
        $headers = [
            'Reference', 'Title', 'Organiser', 'Department', 'Resources',
            'Starts At', 'Ends At', 'Duration (min)', 'Status', 'Created At',
        ];

        $rows = Booking::query()
            ->with(['organiser', 'resources'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->when($request->integer('resource'), fn ($query, $resource) => $query->whereHas('resources', fn ($q) => $q->whereKey($resource)))
            ->latest('starts_at')
            ->cursor()
            ->map(fn (Booking $booking): array => [
                $booking->reference,
                $booking->title,
                $booking->organiser?->name,
                $booking->department,
                $booking->resources->pluck('name')->join(', '),
                $booking->starts_at?->toDateTimeString(),
                $booking->ends_at?->toDateTimeString(),
                $booking->durationInMinutes(),
                $booking->status->label(),
                $booking->created_at?->toDateTimeString(),
            ]);

        return $this->stream('bookings.csv', $headers, $rows);
    }

    public function resources(Request $request): StreamedResponse
    {
        $headers = [
            'Code', 'Name', 'Type', 'Location', 'Status', 'Capacity',
            'Approval Mode', 'Manager', 'Bookable', 'Created At',
        ];

        $rows = Resource::query()
            ->with(['type', 'manager'])
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->orderBy('code')
            ->cursor()
            ->map(fn (Resource $resource): array => [
                $resource->code,
                $resource->name,
                $resource->type?->name,
                $resource->location,
                $resource->status->label(),
                $resource->capacity,
                $resource->approval_mode->label(),
                $resource->manager?->name,
                $resource->is_bookable ? 'Yes' : 'No',
                $resource->created_at?->toDateTimeString(),
            ]);

        return $this->stream('resources.csv', $headers, $rows);
    }

    public function utilisation(Request $request): StreamedResponse
    {
        $headers = ['Code', 'Resource', 'Type', 'Bookings', 'Booked Hours', 'Completed', 'Cancelled', 'No-shows'];

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();

        $rows = Resource::query()
            ->with('type')
            ->withCount([
                'bookings as bookings_count' => fn ($query) => $query->whereBetween('bookings.starts_at', [$from, $to]),
                'bookings as completed_count' => fn ($query) => $query->where('bookings.status', BookingStatus::Completed->value)->whereBetween('bookings.starts_at', [$from, $to]),
                'bookings as cancelled_count' => fn ($query) => $query->where('bookings.status', BookingStatus::Cancelled->value)->whereBetween('bookings.starts_at', [$from, $to]),
                'bookings as no_show_count' => fn ($query) => $query->where('bookings.status', BookingStatus::NoShow->value)->whereBetween('bookings.starts_at', [$from, $to]),
            ])
            ->orderBy('name')
            ->cursor()
            ->map(function (Resource $resource) use ($from, $to): array {
                $minutes = $resource->bookings()
                    ->whereIn('bookings.status', array_map(fn (BookingStatus $status) => $status->value, BookingStatus::fulfilled()))
                    ->whereBetween('bookings.starts_at', [$from, $to])
                    ->get()
                    ->sum(fn (Booking $booking): int => $booking->durationInMinutes());

                return [
                    $resource->code,
                    $resource->name,
                    $resource->type?->name,
                    $resource->bookings_count,
                    round($minutes / 60, 1),
                    $resource->completed_count,
                    $resource->cancelled_count,
                    $resource->no_show_count,
                ];
            });

        return $this->stream('utilisation.csv', $headers, $rows);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    protected function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->insertOne($headers);

            foreach ($rows as $row) {
                $csv->insertOne($this->sanitizeRow($row));
            }
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Neutralise spreadsheet formula injection by prefixing cells that start
     * with a formula trigger character with an apostrophe.
     *
     * @param  array<int, mixed>  $row
     * @return array<int, mixed>
     */
    protected function sanitizeRow(array $row): array
    {
        return array_map(function (mixed $value): mixed {
            if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
                return "'".$value;
            }

            return $value;
        }, $row);
    }
}
