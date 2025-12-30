<template>
  <q-card
    class="mobile-list-card"
    :class="{ 'clickable': clickable }"
    @click="handleClick"
  >
    <q-card-section class="card-content">
      <div class="card-header">
        <div class="card-icon-wrapper" :style="{ backgroundColor: iconBg || 'rgba(156, 39, 176, 0.1)' }">
          <q-icon :name="icon" :size="iconSize || '24px'" :color="iconColor || 'primary'" />
        </div>
        <div class="card-main">
          <div class="card-title">{{ title }}</div>
          <div v-if="subtitle" class="card-subtitle">{{ subtitle }}</div>
        </div>
        <div v-if="badge" class="card-badge">
          <q-badge :color="badgeColor || 'primary'" :label="badge" />
        </div>
        <q-icon name="chevron_right" class="card-arrow" color="grey-5" />
      </div>
      <div v-if="description" class="card-description">{{ description }}</div>
      <div v-if="$slots.extra" class="card-extra">
        <slot name="extra" />
      </div>
    </q-card-section>
  </q-card>
</template>

<script setup>
const props = defineProps({
  title: {
    type: String,
    required: true,
  },
  subtitle: {
    type: String,
    default: '',
  },
  description: {
    type: String,
    default: '',
  },
  icon: {
    type: String,
    default: 'info',
  },
  iconSize: {
    type: String,
    default: '24px',
  },
  iconColor: {
    type: String,
    default: 'primary',
  },
  iconBg: {
    type: String,
    default: null,
  },
  badge: {
    type: String,
    default: null,
  },
  badgeColor: {
    type: String,
    default: 'primary',
  },
  clickable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(['click']);

function handleClick() {
  if (props.clickable) {
    emit('click');
  }
}
</script>

<style lang="scss" scoped>
.mobile-list-card {
  border-radius: var(--radius-lg);
  border: none;
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
  background: var(--bg-card);
  margin-bottom: var(--spacing-md);
  
  &.clickable {
    cursor: pointer;
    
    &:active {
      transform: scale(0.98);
    }
    
    @media (min-width: 768px) {
      &:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-md);
      }
    }
  }
  
  @media (min-width: 768px) {
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
  }
}

.card-content {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.card-header {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-xs);
}

.card-icon-wrapper {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-md);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  
  @media (min-width: 768px) {
    width: 56px;
    height: 56px;
  }
}

.card-main {
  flex: 1;
  min-width: 0;
}

.card-title {
  font-size: var(--font-size-base);
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.3;
  margin-bottom: 2px;
  
  @media (min-width: 768px) {
    font-size: var(--font-size-lg);
  }
}

.card-subtitle {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.4;
  margin-top: 2px;
}

.card-badge {
  flex-shrink: 0;
}

.card-arrow {
  flex-shrink: 0;
  font-size: 20px;
}

.card-description {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  line-height: 1.5;
  margin-top: var(--spacing-sm);
  padding-left: calc(48px + var(--spacing-md));
  
  @media (min-width: 768px) {
    padding-left: calc(56px + var(--spacing-md));
  }
}

.card-extra {
  margin-top: var(--spacing-sm);
  padding-left: calc(48px + var(--spacing-md));
  
  @media (min-width: 768px) {
    padding-left: calc(56px + var(--spacing-md));
  }
}
</style>

