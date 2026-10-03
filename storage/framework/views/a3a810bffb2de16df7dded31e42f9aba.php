<?php

use App\Enums\BookingStatus;
use App\Enums\ResourceStatus;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

?>

<div class="py-7 sm:py-9">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => __('Dashboard'),'subtitle' => $canSeeAll ? __('Today\'s operations across all resources.') : __('Your bookings at a glance.')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Dashboard')),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($canSeeAll ? __('Today\'s operations across all resources.') : __('Your bookings at a glance.'))]); ?>
             <?php $__env->slot('actions', null, []); ?> 
                <a href="<?php echo e(route('calendar')); ?>" wire:navigate class="btn btn-outline btn-sm rounded-xl"><?php echo e(__('Calendar')); ?></a>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\Booking::class)): ?>
                    <a href="<?php echo e(route('bookings.create')); ?>" wire:navigate class="btn btn-primary btn-sm rounded-xl"><?php echo e(__('New booking')); ?></a>
                <?php endif; ?>
             <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $attributes = $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e)): ?>
<?php $component = $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e; ?>
<?php unset($__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e); ?>
<?php endif; ?>

        <section aria-label="<?php echo e(__('Summary')); ?>" class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a href="<?php echo e(route('bookings.index')); ?>" wire:navigate class="card group bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60"><?php echo e(__("Today's bookings")); ?></p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-base-content"><?php echo e(number_format($todayCount)); ?></p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-primary/10 text-primary" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="16" rx="2.5"/><path stroke-linecap="round" d="M3 9.5h18M8 3v4M16 3v4"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50"><?php echo e(__('Scheduled for today')); ?> <span class="ms-1 text-primary">→</span></p>
            </a>

            <a href="<?php echo e(route('bookings.index')); ?>" wire:navigate class="card bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-info/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60"><?php echo e(__('Next 7 days')); ?></p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-info"><?php echo e(number_format($weekCount)); ?></p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-info/10 text-info" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 2m6-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50"><?php echo e(__('Upcoming active bookings')); ?> <span class="ms-1 text-info">→</span></p>
            </a>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('approve-bookings')): ?>
                <a href="<?php echo e(route('approvals.index')); ?>" wire:navigate class="card bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-warning/30 hover:shadow-lg">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-base-content/60"><?php echo e(__('Pending approvals')); ?></p>
                            <p class="mt-3 text-3xl font-bold tracking-tight <?php echo e($pendingCount > 0 ? 'text-warning' : 'text-base-content'); ?>"><?php echo e(number_format($pendingCount)); ?></p>
                        </div>
                        <span class="grid size-11 place-items-center rounded-2xl bg-warning/10 text-warning" aria-hidden="true">
                            <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.5 11 14.5 15.5 10M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z"/></svg>
                        </span>
                    </div>
                    <p class="mt-3 text-xs text-base-content/50"><?php echo e(__('Awaiting your decision')); ?> <span class="ms-1 text-warning">→</span></p>
                </a>
            <?php endif; ?>

            <a href="<?php echo e(route('resources.index')); ?>" wire:navigate class="card bg-base-100 p-5 transition hover:-translate-y-0.5 hover:border-success/30 hover:shadow-lg">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium text-base-content/60"><?php echo e(__('Active resources')); ?></p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-success"><?php echo e(number_format($activeResources)); ?></p>
                    </div>
                    <span class="grid size-11 place-items-center rounded-2xl bg-success/10 text-success" aria-hidden="true">
                        <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Z"/><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7.8 7.5 4.4 7.5-4.4M12 12.2V21"/></svg>
                    </span>
                </div>
                <p class="mt-3 text-xs text-base-content/50"><?php echo e(__('of :total total', ['total' => $totalResources])); ?> <span class="ms-1 text-success">→</span></p>
            </a>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="card bg-base-100 p-5 sm:p-6 lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-base-content"><?php echo e(__("Today's schedule")); ?></h2>
                        <p class="mt-1 text-sm text-base-content/55"><?php echo e(now()->format('l, d M Y')); ?></p>
                    </div>
                    <a href="<?php echo e(route('calendar')); ?>" wire:navigate class="link link-hover text-xs font-medium text-primary"><?php echo e(__('Open calendar')); ?></a>
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $todayBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li>
                            <a href="<?php echo e(route('bookings.show', $booking)); ?>" wire:navigate class="flex items-start gap-4 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                <div class="w-16 shrink-0 text-sm font-semibold tabular-nums text-primary"><?php echo e($booking->starts_at->format('H:i')); ?></div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-base-content"><?php echo e($booking->title); ?></p>
                                    <p class="truncate text-xs text-base-content/55"><?php echo e($booking->resources->pluck('name')->join(', ')); ?></p>
                                </div>
                                <?php if (isset($component)) { $__componentOriginal8c81617a70e11bcf247c4db924ab1b62 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status-badge','data' => ['status' => $booking->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($booking->status)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8c81617a70e11bcf247c4db924ab1b62)): ?>
<?php $attributes = $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62; ?>
<?php unset($__attributesOriginal8c81617a70e11bcf247c4db924ab1b62); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8c81617a70e11bcf247c4db924ab1b62)): ?>
<?php $component = $__componentOriginal8c81617a70e11bcf247c4db924ab1b62; ?>
<?php unset($__componentOriginal8c81617a70e11bcf247c4db924ab1b62); ?>
<?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="rounded-xl bg-base-200/60 px-4 py-8 text-center text-sm text-base-content/60"><?php echo e(__('Nothing scheduled today.')); ?></li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </ul>
            </div>

            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Most booked this month')); ?></h2>
                </div>
                <ul class="mt-4 space-y-4">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $topResources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resource): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <a href="<?php echo e(route('resources.show', $resource)); ?>" wire:navigate class="min-w-0 truncate text-base-content/80 hover:text-primary"><?php echo e($resource->name); ?></a>
                                <span class="font-semibold tabular-nums text-base-content"><?php echo e(number_format($resource->bookings_this_month)); ?></span>
                            </div>
                            <progress class="progress progress-primary h-1.5 w-full" value="<?php echo e($resource->bookings_this_month); ?>" max="<?php echo e(max(1, $topResources->max('bookings_this_month'))); ?>"></progress>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60"><?php echo e(__('No bookings yet.')); ?></li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </ul>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="card bg-base-100 p-5 sm:p-6">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Upcoming')); ?></h2>
                    <a href="<?php echo e(route('bookings.index')); ?>" wire:navigate class="link link-hover text-xs font-medium text-primary"><?php echo e(__('View all')); ?></a>
                </div>
                <ul class="mt-4 divide-y divide-base-200">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $upcomingBookings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li>
                            <a href="<?php echo e(route('bookings.show', $booking)); ?>" wire:navigate class="flex items-center justify-between gap-3 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-base-content"><?php echo e($booking->title); ?></p>
                                    <p class="truncate text-xs text-base-content/55"><?php echo e($booking->resources->pluck('name')->join(', ')); ?></p>
                                </div>
                                <span class="shrink-0 text-xs tabular-nums text-base-content/60"><?php echo e($booking->starts_at->format('d M H:i')); ?></span>
                            </a>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60"><?php echo e(__('No upcoming bookings.')); ?></li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </ul>
            </div>

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('approve-bookings')): ?>
                <div class="card bg-base-100 p-5 sm:p-6">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Awaiting your approval')); ?></h2>
                        <a href="<?php echo e(route('approvals.index')); ?>" wire:navigate class="link link-hover text-xs font-medium text-primary"><?php echo e(__('Review')); ?></a>
                    </div>
                    <ul class="mt-4 divide-y divide-base-200">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $pendingApprovals; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <li>
                                <a href="<?php echo e(route('bookings.show', $booking)); ?>" wire:navigate class="flex items-center justify-between gap-3 rounded-lg py-3 transition hover:px-2 hover:bg-base-200/70">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-base-content"><?php echo e($booking->title); ?></p>
                                        <p class="truncate text-xs text-base-content/55"><?php echo e($booking->organiser?->name); ?> · <?php echo e($booking->resources->pluck('name')->join(', ')); ?></p>
                                    </div>
                                    <span class="shrink-0 text-xs tabular-nums text-base-content/60"><?php echo e($booking->starts_at->format('d M H:i')); ?></span>
                                </a>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60"><?php echo e(__('Nothing awaiting approval.')); ?></li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div><?php /**PATH /home/dev/projects/BookingKOM/resources/views/livewire/dashboard.blade.php ENDPATH**/ ?>