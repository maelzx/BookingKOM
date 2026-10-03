<?php

use App\Enums\ResourceStatus;
use App\Models\Resource;
use App\Models\ResourceType;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

?>

<div class="py-8 sm:py-10">
    <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
        <?php if (isset($component)) { $__componentOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalf8d4ea307ab1e58d4e472a43c8548d8e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.page-header','data' => ['title' => __('Resources'),'subtitle' => __('Everything bookable: rooms, vehicles, equipment, people and facilities.')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Resources')),'subtitle' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(__('Everything bookable: rooms, vehicles, equipment, people and facilities.'))]); ?>
             <?php $__env->slot('actions', null, []); ?> 
                <a href="<?php echo e(route('calendar')); ?>" wire:navigate class="btn btn-outline btn-sm rounded-xl"><?php echo e(__('Calendar')); ?></a>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', \App\Models\Resource::class)): ?>
                    <a href="<?php echo e(route('resources.create')); ?>" wire:navigate class="btn btn-primary btn-sm rounded-xl"><?php echo e(__('New resource')); ?></a>
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

        <div class="card bg-base-100 p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <label for="resource-search" class="sr-only"><?php echo e(__('Search resources')); ?></label>
                    <?php if (isset($component)) { $__componentOriginal18c21970322f9e5c938bc954620c12bb = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal18c21970322f9e5c938bc954620c12bb = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.text-input','data' => ['wire:model.live.debounce.300ms' => 'search','id' => 'resource-search','type' => 'search','class' => 'block w-full','placeholder' => ''.e(__('Search name, code, location…')).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('text-input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['wire:model.live.debounce.300ms' => 'search','id' => 'resource-search','type' => 'search','class' => 'block w-full','placeholder' => ''.e(__('Search name, code, location…')).'']); ?>
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

                <label for="resource-type-filter" class="sr-only"><?php echo e(__('Type')); ?></label>
                <select wire:model.live="type" id="resource-type-filter" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value=""><?php echo e(__('All types')); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $types; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($option->id); ?>"><?php echo e($option->name); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>

                <label for="resource-status-filter" class="sr-only"><?php echo e(__('Status')); ?></label>
                <select wire:model.live="status" id="resource-status-filter" class="select select-bordered w-full border-base-300 focus:border-primary focus:ring-primary">
                    <option value=""><?php echo e(__('All statuses')); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($option->value); ?>"><?php echo e($option->label()); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </select>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-base-200 pt-3">
                <p class="text-xs text-base-content/55" aria-live="polite">
                    <?php echo e(__('Showing :from–:to of :total resources', ['from' => $resources->firstItem() ?? 0, 'to' => $resources->lastItem() ?? 0, 'total' => $resources->total()])); ?>

                    <span wire:loading class="ms-1 text-primary"><?php echo e(__('Updating…')); ?></span>
                </p>

                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($search !== '' || $type || $status !== ''): ?>
                    <button type="button" wire:click="resetFilters" class="btn btn-ghost btn-xs rounded-lg text-base-content/70"><?php echo e(__('Clear filters')); ?></button>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </div>

        <?php if (isset($component)) { $__componentOriginalc8463834ba515134d5c98b88e1a9dc03 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc8463834ba515134d5c98b88e1a9dc03 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.data-table','data' => ['paginator' => $resources]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('data-table'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['paginator' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($resources)]); ?>
             <?php $__env->slot('table', null, []); ?> 
                <table class="table table-sm">
                    <thead class="bg-base-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60"><?php echo e(__('Code')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60"><?php echo e(__('Name')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60"><?php echo e(__('Type')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60"><?php echo e(__('Location')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60"><?php echo e(__('Approval')); ?></th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-base-content/60"><?php echo e(__('Status')); ?></th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-base-300">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $resources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resource): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr wire:key="resource-<?php echo e($resource->id); ?>" class="hover:bg-base-200">
                                <td class="px-4 py-3 font-mono text-sm text-base-content"><?php echo e($resource->code); ?></td>
                                <td class="px-4 py-3 text-sm text-base-content">
                                    <a href="<?php echo e(route('resources.show', $resource)); ?>" wire:navigate class="hover:text-primary"><?php echo e($resource->name); ?></a>
                                </td>
                                <td class="px-4 py-3 text-sm text-base-content/60"><?php echo e($resource->type?->name ?? '—'); ?></td>
                                <td class="px-4 py-3 text-sm text-base-content/60"><?php echo e($resource->location ?? '—'); ?></td>
                                <td class="px-4 py-3 text-sm text-base-content/60"><?php echo e($resource->approval_mode->label()); ?></td>
                                <td class="px-4 py-3 text-sm"><?php if (isset($component)) { $__componentOriginal8c81617a70e11bcf247c4db924ab1b62 = $component; } ?>
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
<?php endif; ?></td>
                                <td class="px-4 py-3 text-right text-sm whitespace-nowrap">
                                    <a href="<?php echo e(route('resources.show', $resource)); ?>" wire:navigate class="text-primary hover:opacity-80"><?php echo e(__('View')); ?></a>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $resource)): ?>
                                        <a href="<?php echo e(route('resources.edit', $resource)); ?>" wire:navigate class="ms-3 text-base-content/70 hover:text-base-content"><?php echo e(__('Edit')); ?></a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $resource)): ?>
                                        <button wire:click="delete(<?php echo e($resource->id); ?>)" wire:confirm="<?php echo e(__('Delete this resource?')); ?>" class="ms-3 text-error hover:opacity-80"><?php echo e(__('Delete')); ?></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-base-content/60"><?php echo e(__('No resources found.')); ?></td>
                            </tr>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </tbody>
                </table>
             <?php $__env->endSlot(); ?>

             <?php $__env->slot('cards', null, []); ?> 
                <ul class="divide-y divide-base-300">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $resources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resource): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <li class="space-y-2 p-4" wire:key="resource-card-<?php echo e($resource->id); ?>">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="<?php echo e(route('resources.show', $resource)); ?>" wire:navigate class="font-medium text-base-content hover:text-primary"><?php echo e($resource->name); ?></a>
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

                            <dl class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-base-content/60">
                                <div><dt class="inline text-base-content/40"><?php echo e(__('Type')); ?>:</dt> <?php echo e($resource->type?->name ?? '—'); ?></div>
                                <div><dt class="inline text-base-content/40"><?php echo e(__('Location')); ?>:</dt> <?php echo e($resource->location ?? '—'); ?></div>
                                <div><dt class="inline text-base-content/40"><?php echo e(__('Approval')); ?>:</dt> <?php echo e($resource->approval_mode->label()); ?></div>
                            </dl>

                            <div class="flex items-center gap-4 pt-1 text-sm">
                                <a href="<?php echo e(route('resources.show', $resource)); ?>" wire:navigate class="text-primary hover:opacity-80"><?php echo e(__('View')); ?></a>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $resource)): ?>
                                    <a href="<?php echo e(route('resources.edit', $resource)); ?>" wire:navigate class="text-base-content/70 hover:text-base-content"><?php echo e(__('Edit')); ?></a>
                                <?php endif; ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $resource)): ?>
                                    <button wire:click="delete(<?php echo e($resource->id); ?>)" wire:confirm="<?php echo e(__('Delete this resource?')); ?>" class="text-error hover:opacity-80"><?php echo e(__('Delete')); ?></button>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <li class="p-8 text-center text-sm text-base-content/60"><?php echo e(__('No resources found.')); ?></li>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </ul>
             <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc8463834ba515134d5c98b88e1a9dc03)): ?>
<?php $attributes = $__attributesOriginalc8463834ba515134d5c98b88e1a9dc03; ?>
<?php unset($__attributesOriginalc8463834ba515134d5c98b88e1a9dc03); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc8463834ba515134d5c98b88e1a9dc03)): ?>
<?php $component = $__componentOriginalc8463834ba515134d5c98b88e1a9dc03; ?>
<?php unset($__componentOriginalc8463834ba515134d5c98b88e1a9dc03); ?>
<?php endif; ?>
    </div>
</div><?php /**PATH /home/dev/projects/BookingKOM/resources/views/livewire/resources/index.blade.php ENDPATH**/ ?>