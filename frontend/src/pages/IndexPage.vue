<template>
  <q-page class="index-page flex flex-center">
    <div class="index-container text-center">
      <div class="row items-center justify-center q-mb-lg">
        <q-icon name="school" size="64px" color="primary" class="q-mr-md" />
        <div class="text-h3 text-weight-bold text-primary">SMS</div>
      </div>
      <div class="text-h4 text-weight-bold q-mb-sm">Welcome to School Management System</div>
      <p class="text-h6 text-grey-7 q-mb-xl">Navigate the future of education</p>
      <div class="row q-gutter-md justify-center">
        <q-btn
          color="primary"
          label="Sign In"
          to="/login"
          unelevated
          size="lg"
          class="action-button"
          icon="login"
        />
        <q-btn
          outline
          color="primary"
          label="Sign Up"
          to="/register"
          size="lg"
          class="action-button"
          icon="person_add"
        />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { onMounted } from 'vue';

onMounted(() => {
  console.log('IndexPage mounted');
});
</script>

<style lang="scss" scoped>
.index-page {
  min-height: 100vh;
  position: relative;
}

.index-container {
  position: relative;
  z-index: 1;
  animation: fadeIn 0.8s cubic-bezier(0.4, 0, 0.2, 1);
  padding: 24px;
}

.action-button {
  border-radius: 12px;
  text-transform: none;
  font-weight: 600;
  letter-spacing: 0.5px;
  padding: 12px 32px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  
  &:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(156, 39, 176, 0.3);
  }
  
  &:active {
    transform: translateY(0);
  }
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>


