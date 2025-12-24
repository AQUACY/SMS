<template>
  <q-layout view="hHh lpR fFf" class="auth-layout">
    <q-page-container>
      <transition name="page-fade" mode="out-in">
        <router-view />
      </transition>
    </q-page-container>
  </q-layout>
</template>

<script setup>
// Simple layout for authentication pages - no sidebar or header
</script>

<style lang="scss" scoped>
.auth-layout {
  background: linear-gradient(135deg, rgba(156, 39, 176, 0.1) 0%, rgba(33, 150, 243, 0.1) 100%);
  min-height: 100vh;
  position: relative;
  
  &::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
      radial-gradient(circle at 20% 50%, rgba(156, 39, 176, 0.15) 0%, transparent 50%),
      radial-gradient(circle at 80% 80%, rgba(33, 150, 243, 0.15) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
  }
  
  :deep(.q-page-container) {
    position: relative;
    z-index: 1;
  }
}

// Page transition animations
.page-fade-enter-active,
.page-fade-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.page-fade-enter-from {
  opacity: 0;
  transform: translateY(20px) scale(0.95);
}

.page-fade-leave-to {
  opacity: 0;
  transform: translateY(-20px) scale(0.95);
}
</style>

