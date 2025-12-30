<template>
  <q-page class="subscriptions-list-page">
    <MobilePageHeader
      title="Subscriptions"
      subtitle="All subscriptions"
    />

    <div class="page-content">
      <MobileCard variant="default" padding="lg">
        <div class="empty-state">
          <q-icon name="subscriptions" size="64px" color="grey-5" />
          <div class="empty-text">Subscriptions</div>
          <div class="empty-subtext">Subscriptions list will be implemented here</div>
        </div>
      </MobileCard>
    </div>
  </q-page>
</template>

<script setup>
import MobilePageHeader from 'src/components/mobile/MobilePageHeader.vue';
import MobileCard from 'src/components/mobile/MobileCard.vue';
</script>

<style lang="scss" scoped>
.subscriptions-list-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.page-content {
  max-width: 900px;
  margin: 0 auto;
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: var(--spacing-2xl);
  text-align: center;
}

.empty-text {
  font-size: var(--font-size-lg);
  font-weight: 600;
  color: var(--text-primary);
  margin-top: var(--spacing-md);
}

.empty-subtext {
  font-size: var(--font-size-base);
  color: var(--text-secondary);
  margin-top: var(--spacing-sm);
}
</style>

