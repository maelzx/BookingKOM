<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Resource;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => __('Reports'),'subtitle' => __('Utilisation, demand and booking outcomes.')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Reports')),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Utilisation, demand and booking outcomes.'))]); ?>
             <?php $__env->slot('actions', null, []); ?> 
                <a href="<?php echo e(route('exports.bookings', ['from' => $from, 'to' => $to])); ?>" class="btn btn-outline btn-sm rounded-xl"><?php echo e(__('Export bookings')); ?></a>
                <a href="<?php echo e(route('exports.utilisation', ['from' => $from, 'to' => $to])); ?>" class="btn btn-outline btn-sm rounded-xl"><?php echo e(__('Export utilisation')); ?></a>
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

        <div class="card bg-base-100 p-4">
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'from','value' => __('From')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'from','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('From'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['wire:model.live' => 'from','id' => 'from','type' => 'date','class' => 'mt-1 block']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model.live' => 'from','id' => 'from','type' => 'date','class' => 'mt-1 block']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                </div>
                <div>
                    <?php if (isset($component)) { $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.input-label','data' => ['for' => 'to','value' => __('To')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('input-label'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['for' => 'to','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('To'))]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $attributes = $__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__attributesOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
<?php if (isset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581)): ?>
<?php $component = $__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581; ?>
<?php unset($__componentOriginale3da9d84bb64e4bc2eeebaafabfb2581); ?>
<?php endif; ?>
                    <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['wire:model.live' => 'to','id' => 'to','type' => 'date','class' => 'mt-1 block']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model.live' => 'to','id' => 'to','type' => 'date','class' => 'mt-1 block']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $attributes = $__attributesOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__attributesOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal18c21970322f9e5c938bc954620c12bb)): ?>
<?php $component = $__componentOriginal18c21970322f9e5c938bc954620c12bb; ?>
<?php unset($__componentOriginal18c21970322f9e5c938bc954620c12bb); ?>
<?php endif; ?>
                </div>
                <p class="text-xs text-base-content/55">
                    <?php echo e($rangeFrom->format('d M Y')); ?> – <?php echo e($rangeTo->format('d M Y')); ?>

                    <span wire:loading class="ms-1 text-primary"><?php echo e(__('Updating…')); ?></span>
                </p>
            </div>
        </div>

        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card bg-base-100 p-5">
                <p class="text-sm text-base-content/60"><?php echo e(__('Total bookings')); ?></p>
                <p class="mt-2 text-2xl font-bold text-base-content"><?php echo e(number_format($total)); ?></p>
            </div>
            <div class="card bg-base-100 p-5">
                <p class="text-sm text-base-content/60"><?php echo e(__('Booked hours')); ?></p>
                <p class="mt-2 text-2xl font-bold text-primary"><?php echo e(number_format($bookedHours, 1)); ?></p>
            </div>
            <div class="card bg-base-100 p-5">
                <p class="text-sm text-base-content/60"><?php echo e(__('Completed')); ?></p>
                <p class="mt-2 text-2xl font-bold text-success"><?php echo e(number_format($completed)); ?></p>
            </div>
            <div class="card bg-base-100 p-5">
                <p class="text-sm text-base-content/60"><?php echo e(__('Cancelled')); ?></p>
                <p class="mt-2 text-2xl font-bold text-error"><?php echo e(number_format($cancelled)); ?></p>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="card bg-base-100 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Most booked resources')); ?></h2>
                <div class="mt-4 overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th class="text-left text-xs uppercase tracking-wider text-base-content/60"><?php echo e(__('Resource')); ?></th>
                                <th class="text-right text-xs uppercase tracking-wider text-base-content/60"><?php echo e(__('Bookings')); ?></th>
                                <th class="text-right text-xs uppercase tracking-wider text-base-content/60"><?php echo e(__('Fulfilled')); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $byResource; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resource): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td class="text-sm">
                                        <a href="<?php echo e(route('resources.show', $resource)); ?>" wire:navigate class="hover:text-primary"><?php echo e($resource->name); ?></a>
                                        <span class="block text-xs text-base-content/50"><?php echo e($resource->type?->name); ?></span>
                                    </td>
                                    <td class="text-right text-sm tabular-nums"><?php echo e($resource->period_count); ?></td>
                                    <td class="text-right text-sm tabular-nums text-base-content/60"><?php echo e($resource->fulfilled_count); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="3" class="py-6 text-center text-sm text-base-content/60"><?php echo e(__('No bookings in this period.')); ?></td></tr>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-5">
                <div class="card bg-base-100 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Bookings by department')); ?></h2>
                    <ul class="mt-4 space-y-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $byDepartment; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <li>
                                <div class="mb-1 flex items-center justify-between text-sm">
                                    <span class="text-base-content/80"><?php echo e($row->dept); ?></span>
                                    <span class="font-semibold tabular-nums"><?php echo e($row->total); ?></span>
                                </div>
                                <progress class="progress progress-primary h-1.5 w-full" value="<?php echo e($row->total); ?>" max="<?php echo e(max(1, $byDepartment->max('total'))); ?>"></progress>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60"><?php echo e(__('No data.')); ?></li>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </ul>
                </div>

                <div class="card bg-base-100 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Outcomes')); ?></h2>
                    <ul class="mt-4 divide-y divide-base-200 text-sm">
                        <li class="flex items-center justify-between py-2.5"><span class="text-base-content/70"><?php echo e(__('Confirmed')); ?></span><span class="font-semibold tabular-nums"><?php echo e($confirmed); ?></span></li>
                        <li class="flex items-center justify-between py-2.5"><span class="text-base-content/70"><?php echo e(__('Completed')); ?></span><span class="font-semibold tabular-nums"><?php echo e($completed); ?></span></li>
                        <li class="flex items-center justify-between py-2.5"><span class="text-base-content/70"><?php echo e(__('Rejected')); ?></span><span class="font-semibold tabular-nums"><?php echo e($rejected); ?></span></li>
                        <li class="flex items-center justify-between py-2.5"><span class="text-base-content/70"><?php echo e(__('No-shows')); ?></span><span class="font-semibold tabular-nums text-error"><?php echo e($noShow); ?></span></li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="card bg-base-100 p-5 sm:p-6">
            <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Top organisers')); ?></h2>
            <ul class="mt-4 divide-y divide-base-200">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $topOrganisers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <span class="text-base-content/80"><?php echo e($row->organiser?->name ?? __('Unknown')); ?></span>
                        <span class="rounded-lg bg-base-200 px-2.5 py-1 text-xs font-semibold tabular-nums"><?php echo e($row->total); ?></span>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="rounded-xl bg-base-200/60 px-4 py-6 text-center text-sm text-base-content/60"><?php echo e(__('No data.')); ?></li>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </ul>
        </section>
    </div>
</div><?php /**PATH /home/dev/projects/BookingKOM/resources/views/livewire/reports/index.blade.php ENDPATH**/ ?>