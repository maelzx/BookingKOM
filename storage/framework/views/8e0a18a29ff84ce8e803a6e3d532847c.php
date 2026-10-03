<?php

use App\Enums\BookingStatus;
use App\Models\Resource;
use App\Services\BookingAvailability;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

?>

<div class="w-full max-w-md space-y-4">
    <div class="card bg-base-100 p-6">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-base-content"><?php echo e($resource->name); ?></h1>
                <p class="font-mono text-xs text-base-content/50"><?php echo e($resource->code); ?></p>
            </div>
            <?php if (isset($component)) { $__componentOriginal8c81617a70e11bcf247c4db924ab1b62 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8c81617a70e11bcf247c4db924ab1b62 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.status-badge','data' => ['status' => $resource->status]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('status-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['status' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($resource->status)]); ?>
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
        </div>

        <dl class="mt-4 grid grid-cols-2 gap-x-4 gap-y-3 border-t border-base-200 pt-4 text-sm">
            <div>
                <dt class="text-xs uppercase tracking-wide text-base-content/45"><?php echo e(__('Type')); ?></dt>
                <dd class="mt-0.5 text-base-content"><?php echo e($resource->type?->name ?? '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-base-content/45"><?php echo e(__('Location')); ?></dt>
                <dd class="mt-0.5 text-base-content"><?php echo e($resource->location ?? '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-base-content/45"><?php echo e(__('Capacity')); ?></dt>
                <dd class="mt-0.5 text-base-content"><?php echo e($resource->capacity ?? '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-base-content/45"><?php echo e(__('Approval')); ?></dt>
                <dd class="mt-0.5 text-base-content"><?php echo e($resource->approval_mode->label()); ?></dd>
            </div>
        </dl>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($resource->description): ?>
            <p class="mt-4 border-t border-base-200 pt-4 text-sm text-base-content/70"><?php echo e($resource->description); ?></p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>

    <div class="card bg-base-100 p-6">
        <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Available today')); ?></h2>
        <div class="mt-3 flex flex-wrap gap-1.5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $slots; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slot): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <span class="rounded-md px-2 py-1 text-xs font-medium tabular-nums <?php echo e($slot['available'] ? 'bg-success/10 text-success' : 'bg-error/10 text-error line-through'); ?>">
                    <?php echo e($slot['start']->format('H:i')); ?>

                </span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="text-sm text-base-content/60"><?php echo e(__('Not available today.')); ?></p>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>

    <div class="card bg-base-100 p-6">
        <h2 class="text-base font-semibold text-base-content"><?php echo e(__('Upcoming reservations')); ?></h2>
        <ul class="mt-3 divide-y divide-base-200 text-sm">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $upcoming; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $booking): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <li class="flex items-center justify-between gap-3 py-2.5">
                    <span class="tabular-nums text-base-content/80"><?php echo e($booking->starts_at->format('D d M H:i')); ?></span>
                    <span class="text-xs text-base-content/50"><?php echo e($booking->starts_at->format('H:i')); ?>–<?php echo e($booking->ends_at->format('H:i')); ?></span>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <li class="py-3 text-center text-sm text-base-content/60"><?php echo e(__('No upcoming reservations.')); ?></li>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </ul>
    </div>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($resource->isBookable()): ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
            <a href="<?php echo e(route('bookings.create', ['resource' => $resource->id])); ?>" class="btn btn-primary w-full rounded-xl"><?php echo e(__('Book this resource')); ?></a>
        <?php else: ?>
            <a href="<?php echo e(route('login')); ?>" class="btn btn-primary w-full rounded-xl"><?php echo e(__('Sign in to book')); ?></a>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    <?php else: ?>
        <div class="card bg-base-100 p-4 text-center text-sm text-base-content/60"><?php echo e(__('This resource is not currently bookable.')); ?></div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</div><?php /**PATH /home/dev/projects/BookingKOM/resources/views/livewire/scan/resource.blade.php ENDPATH**/ ?>