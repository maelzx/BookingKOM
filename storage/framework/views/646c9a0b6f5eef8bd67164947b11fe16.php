<?php

use Livewire\Volt\Component;

?>

<section>
    <header>
        <h2 class="text-lg font-medium text-base-content"><?php echo e(__('Email notifications')); ?></h2>
        <p class="mt-1 text-sm text-base-content/70"><?php echo e(__('Choose which booking notifications you receive.')); ?></p>
    </header>

    <form wire:submit="update" class="mt-6 space-y-3">
        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_booking_created" class="rounded border-base-300 text-primary focus:ring-primary">
            <span class="text-sm text-base-content/80"><?php echo e(__('Booking confirmations and updates')); ?></span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_approval_required" class="rounded border-base-300 text-primary focus:ring-primary">
            <span class="text-sm text-base-content/80"><?php echo e(__('Approval requests awaiting my decision')); ?></span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_booking_decisions" class="rounded border-base-300 text-primary focus:ring-primary">
            <span class="text-sm text-base-content/80"><?php echo e(__('Approval decisions on my bookings')); ?></span>
        </label>

        <label class="flex items-center gap-2">
            <input type="checkbox" wire:model="notify_booking_reminders" class="rounded border-base-300 text-primary focus:ring-primary">
            <span class="text-sm text-base-content/80"><?php echo e(__('Upcoming booking reminders')); ?></span>
        </label>

        <div class="flex items-center gap-4">
            <?php if (isset($component)) { $__componentOriginald411d1792bd6cc877d687758b753742c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald411d1792bd6cc877d687758b753742c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.primary-button','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('primary-button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?><?php echo e(__('Save')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $attributes = $__attributesOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__attributesOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald411d1792bd6cc877d687758b753742c)): ?>
<?php $component = $__componentOriginald411d1792bd6cc877d687758b753742c; ?>
<?php unset($__componentOriginald411d1792bd6cc877d687758b753742c); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginala665a74688c74e9ee80d4fedd2b98434 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala665a74688c74e9ee80d4fedd2b98434 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.action-message','data' => ['on' => 'saved']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('action-message'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['on' => 'saved']); ?><?php echo e(__('Saved.')); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala665a74688c74e9ee80d4fedd2b98434)): ?>
<?php $attributes = $__attributesOriginala665a74688c74e9ee80d4fedd2b98434; ?>
<?php unset($__attributesOriginala665a74688c74e9ee80d4fedd2b98434); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala665a74688c74e9ee80d4fedd2b98434)): ?>
<?php $component = $__componentOriginala665a74688c74e9ee80d4fedd2b98434; ?>
<?php unset($__componentOriginala665a74688c74e9ee80d4fedd2b98434); ?>
<?php endif; ?>
        </div>
    </form>
</section><?php /**PATH /home/dev/projects/BookingKOM/resources/views/livewire/profile/notification-preferences.blade.php ENDPATH**/ ?>