<template>
  <div class="mobile-page-header">
    <div class="header-top">
      <q-btn
        v-if="showBack"
        flat
        round
        dense
        icon="arrow_back"
        @click="handleBack"
        class="back-btn"
      />
      <div class="header-content">
        <div class="header-title">{{ title }}</div>
        <div v-if="subtitle" class="header-subtitle">{{ subtitle }}</div>
      </div>
      <div class="header-actions">
        <slot name="actions" />
      </div>
    </div>
    <div v-if="$slots.extra" class="header-extra">
      <slot name="extra" />
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';

const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  showBack: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['back']);

const router = useRouter();

function handleBack() {
  emit('back');
  router.back();
}
</script>

<style lang="scss" scoped>
.mobile-page-header {
  margin-bottom: var(--spacing-lg);
  padding: 0 var(--spacing-sm);
  
  @media (min-width: 768px) {
    padding: 0;
  }
}

.header-top {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.back-btn {
  min-width: 40px;
  min-height: 40px;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
}

.header-content {
  flex: 1;
  min-width: 0;
}

.header-title {
  font-size: var(--font-size-2xl);
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
  margin-bottom: var(--spacing-xs);
  
  @media (min-width: 768px) {
    font-size: var(--font-size-3xl);
  }
}

.header-subtitle {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.4;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  flex-shrink: 0;
  
  @media (max-width: 599px) {
    flex-direction: column;
    align-items: stretch;
    
    :deep(.q-btn) {
      min-width: 0;
      width: 100%;
    }
  }
}

.header-extra {
  margin-top: var(--spacing-md);
}
</style>

