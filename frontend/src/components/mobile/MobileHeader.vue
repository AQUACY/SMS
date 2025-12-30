<template>
  <div class="mobile-header" :class="headerClass">
    <div class="header-content">
      <div class="header-left">
        <slot name="left">
          <q-btn
            v-if="showBack"
            flat
            round
            dense
            icon="arrow_back"
            @click="handleBack"
            class="back-btn"
          />
          <q-btn
            v-if="showMenu"
            flat
            round
            dense
            icon="menu"
            @click="handleMenu"
            class="menu-btn"
          />
        </slot>
      </div>
      
      <div class="header-center">
        <slot name="center">
          <div v-if="title" class="header-title">{{ title }}</div>
          <div v-if="subtitle" class="header-subtitle">{{ subtitle }}</div>
        </slot>
      </div>
      
      <div class="header-right">
        <slot name="right">
          <q-btn
            v-if="showSearch"
            flat
            round
            dense
            icon="search"
            @click="handleSearch"
            class="action-btn"
          />
          <q-btn
            v-if="showFilter"
            flat
            round
            dense
            icon="tune"
            @click="handleFilter"
            class="action-btn"
          />
        </slot>
      </div>
    </div>
    
    <div v-if="$slots.extra" class="header-extra">
      <slot name="extra" />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';

const props = defineProps({
  title: {
    type: String,
    default: '',
  },
  subtitle: {
    type: String,
    default: '',
  },
  showBack: {
    type: Boolean,
    default: false,
  },
  showMenu: {
    type: Boolean,
    default: false,
  },
  showSearch: {
    type: Boolean,
    default: false,
  },
  showFilter: {
    type: Boolean,
    default: false,
  },
  transparent: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['back', 'menu', 'search', 'filter']);

const router = useRouter();

const headerClass = computed(() => {
  return {
    'header-transparent': props.transparent,
  };
});

function handleBack() {
  if (props.showBack) {
    emit('back');
    router.back();
  }
}

function handleMenu() {
  emit('menu');
}

function handleSearch() {
  emit('search');
}

function handleFilter() {
  emit('filter');
}
</script>

<style lang="scss" scoped>
.mobile-header {
  background: var(--bg-secondary);
  box-shadow: var(--shadow-sm);
  position: sticky;
  top: 0;
  z-index: 100;
  backdrop-filter: blur(20px);
  background: rgba(255, 255, 255, 0.95);
  
  &.header-transparent {
    background: transparent;
    box-shadow: none;
  }
}

.header-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-sm) var(--spacing-md);
  min-height: 56px;
  
  @media (min-width: 768px) {
    padding: var(--spacing-md) var(--spacing-lg);
    min-height: 64px;
  }
}

.header-left,
.header-right {
  display: flex;
  align-items: center;
  gap: var(--spacing-xs);
  flex-shrink: 0;
}

.header-center {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
  padding: 0 var(--spacing-md);
}

.header-title {
  font-size: var(--font-size-lg);
  font-weight: 700;
  color: var(--text-primary);
  line-height: 1.2;
  
  @media (min-width: 768px) {
    font-size: var(--font-size-xl);
  }
}

.header-subtitle {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
  margin-top: 2px;
}

.back-btn,
.menu-btn,
.action-btn {
  min-width: 40px;
  min-height: 40px;
  width: 40px;
  height: 40px;
  
  @media (min-width: 768px) {
    min-width: 36px;
    min-height: 36px;
    width: 36px;
    height: 36px;
  }
}

.header-extra {
  padding: 0 var(--spacing-md) var(--spacing-sm);
  
  @media (min-width: 768px) {
    padding: 0 var(--spacing-lg) var(--spacing-md);
  }
}
</style>

