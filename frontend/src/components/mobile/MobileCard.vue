<template>
  <q-card
    :class="['mobile-card-component', cardClass]"
    :style="cardStyle"
    @click="handleClick"
  >
    <q-card-section :class="sectionClass">
      <slot />
    </q-card-section>
  </q-card>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  variant: {
    type: String,
    default: 'default', // 'default', 'featured', 'gradient'
    validator: (val) => ['default', 'featured', 'gradient'].includes(val),
  },
  color: {
    type: String,
    default: null,
  },
  clickable: {
    type: Boolean,
    default: false,
  },
  padding: {
    type: String,
    default: 'md', // 'none', 'sm', 'md', 'lg'
  },
});

const emit = defineEmits(['click']);

const cardClass = computed(() => {
  const classes = [];
  if (props.variant === 'featured') classes.push('featured-card');
  if (props.variant === 'gradient') classes.push('gradient-card');
  if (props.color) classes.push(`card-${props.color}`);
  if (props.clickable) classes.push('clickable-card');
  return classes.join(' ');
});

const cardStyle = computed(() => {
  const styles = {};
  if (props.color && props.variant !== 'featured') {
    styles.backgroundColor = `var(--accent-${props.color})`;
    styles.color = 'white';
  }
  return styles;
});

const sectionClass = computed(() => {
  const paddingMap = {
    none: 'q-pa-none',
    sm: 'q-pa-sm',
    md: 'q-pa-md',
    lg: 'q-pa-lg',
  };
  return paddingMap[props.padding] || 'q-pa-md';
});

function handleClick() {
  if (props.clickable) {
    emit('click');
  }
}
</script>

<style lang="scss" scoped>
.mobile-card-component {
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base);
  border: none;
  overflow: hidden;
  
  &.clickable-card {
    cursor: pointer;
    
    &:active {
      transform: scale(0.98);
    }
    
    @media (min-width: 768px) {
      &:hover {
        box-shadow: var(--shadow-md);
        transform: translateY(-2px);
      }
    }
  }
  
  &.featured-card {
    background: var(--primary-gradient);
    color: var(--text-white);
    box-shadow: var(--shadow-colored);
    position: relative;
    overflow: hidden;
    
    &::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
      pointer-events: none;
    }
  }
  
  &.gradient-card {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-pink) 100%);
    color: var(--text-white);
  }
  
  @media (min-width: 768px) {
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
  }
}
</style>

