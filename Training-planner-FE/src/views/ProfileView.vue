<script setup lang="ts">
import DeleteAccount from '@/components/profile/DeleteAccount.vue';
import PasswordForm from '@/components/profile/PasswordForm.vue';
import ProfileForm from '@/components/profile/ProfileForm.vue';
import SubcategoryManager from '@/components/profile/SubcategoryManager.vue';
</script>

<template>
  <div class="profile-view">
    <h1 class="page-title">Profil</h1>
    <div class="profile-grid">
      <div class="stack">
        <ProfileForm />
        <PasswordForm />
        <DeleteAccount />
      </div>
      <SubcategoryManager />
    </div>
  </div>
</template>

<style scoped>
.profile-grid {
  display: grid;
  gap: var(--space-4);
  align-items: start;
}

/* Tablet landscape + desktop: account forms on the left, subcategories on the right */
@media (min-width: 900px) {
  .profile-grid {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  }
}
</style>
