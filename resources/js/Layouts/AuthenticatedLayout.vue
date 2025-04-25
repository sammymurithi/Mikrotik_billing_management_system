<script setup>
import AppLayout from './AppLayout.vue';

defineOptions({
    layout: AppLayout,
});
</script>

<template>
    <AppLayout>
        <template #header>
            <slot name="header" />
        </template>

        <slot />
    </AppLayout>
</template> 