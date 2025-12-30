# Mobile Page Design Patterns

## Overview
This document outlines the mobile-native design patterns established for pages in the School Management System. These patterns ensure consistency across all pages while providing an optimal mobile experience.

## Reusable Components

### 1. MobilePageHeader
**Location**: `frontend/src/components/mobile/MobilePageHeader.vue`

**Usage**: Standard page header with title, subtitle, back button, and action buttons.

```vue
<MobilePageHeader
  title="Page Title"
  subtitle="Page description"
  :show-back="true"
>
  <template v-slot:actions>
    <q-btn color="primary" label="Add" icon="add" />
  </template>
</MobilePageHeader>
```

**Props**:
- `title` (required): Main page title
- `subtitle` (optional): Page description
- `showBack` (optional): Show back button (default: false)

**Slots**:
- `actions`: Action buttons (Add, Import, etc.)
- `extra`: Additional content below header

### 2. MobileListCard
**Location**: `frontend/src/components/mobile/MobileListCard.vue`

**Usage**: Card component for list items on mobile.

```vue
<MobileListCard
  title="Item Title"
  subtitle="Subtitle"
  description="Additional details"
  icon="person"
  badge="Active"
  badge-color="positive"
  icon-bg="rgba(156, 39, 176, 0.1)"
  @click="handleClick"
>
  <template v-slot:extra>
    <q-btn label="Action" />
  </template>
</MobileListCard>
```

**Props**:
- `title` (required): Main title
- `subtitle` (optional): Subtitle text
- `description` (optional): Description text
- `icon` (optional): Icon name (default: 'info')
- `iconSize` (optional): Icon size (default: '24px')
- `iconColor` (optional): Icon color (default: 'primary')
- `iconBg` (optional): Icon background color
- `badge` (optional): Badge text
- `badgeColor` (optional): Badge color (default: 'primary')
- `clickable` (optional): Make card clickable (default: true)

**Slots**:
- `extra`: Additional content (actions, details, etc.)

## Page Patterns

### Pattern 1: List Pages

**Structure**:
- MobilePageHeader at top
- Mobile card view for mobile (< 960px)
- Desktop table view for desktop (>= 960px)

**Example**: StudentsListPage, ClassesListPage

```vue
<template>
  <q-page class="page-name-list-page">
    <MobilePageHeader title="Items" subtitle="Manage all items">
      <template v-slot:actions>
        <!-- Action buttons -->
      </template>
    </MobilePageHeader>

    <!-- Mobile Card View -->
    <div class="mobile-list-view">
      <div v-if="loading" class="loading-cards">
        <!-- Skeleton loaders -->
      </div>
      
      <div v-else-if="items.length > 0" class="cards-container">
        <MobileListCard
          v-for="item in items"
          :key="item.id"
          :title="item.name"
          :subtitle="item.subtitle"
          :description="item.description"
          icon="icon-name"
          :badge="item.status"
          @click="viewItem(item.id)"
        />
      </div>
      
      <div v-else class="empty-state">
        <q-icon name="icon" size="64px" color="grey-5" />
        <div class="empty-text">No items found</div>
      </div>
    </div>

    <!-- Desktop Table View -->
    <div class="desktop-table-view">
      <q-card class="widget-card">
        <q-card-section>
          <q-table :rows="items" :columns="columns" />
        </q-card-section>
      </q-card>
    </div>
  </q-page>
</template>
```

**Styles**:
```scss
.page-name-list-page {
  padding: var(--spacing-md);
  
  @media (min-width: 768px) {
    padding: var(--spacing-lg);
  }
}

.mobile-list-view {
  display: block;
  
  @media (min-width: 960px) {
    display: none;
  }
}

.desktop-table-view {
  display: none;
  
  @media (min-width: 960px) {
    display: block;
  }
}
```

### Pattern 2: Detail Pages

**Structure**:
- MobilePageHeader with back button
- Card-based layout for information sections
- MobileCard components for sections

**Example**: StudentDetailPage, TeacherDetailPage

```vue
<template>
  <q-page class="page-name-detail-page">
    <MobilePageHeader
      title="Item Details"
      subtitle="View and manage information"
      :show-back="true"
    >
      <template v-slot:actions>
        <q-btn color="primary" label="Edit" icon="edit" />
      </template>
    </MobilePageHeader>

    <div v-if="loading" class="loading-skeleton">
      <!-- Skeleton loaders -->
    </div>

    <div v-else class="detail-content">
      <MobileCard variant="default" padding="lg">
        <!-- Content -->
      </MobileCard>
    </div>
  </q-page>
</template>
```

### Pattern 3: Form Pages

**Structure**:
- MobilePageHeader with back button
- Form in MobileCard
- Mobile-optimized form fields

**Example**: StudentCreatePage, StudentEditPage

```vue
<template>
  <q-page class="page-name-form-page">
    <MobilePageHeader
      title="Create Item"
      subtitle="Add new item"
      :show-back="true"
    />

    <MobileCard variant="default" padding="lg">
      <q-form @submit="handleSubmit">
        <!-- Form fields -->
        <div class="form-actions">
          <q-btn type="submit" color="primary" label="Save" />
          <q-btn flat label="Cancel" @click="router.back()" />
        </div>
      </q-form>
    </MobileCard>
  </q-page>
</template>
```

## Design Tokens

### Spacing
- Use CSS variables: `var(--spacing-xs)`, `var(--spacing-sm)`, `var(--spacing-md)`, `var(--spacing-lg)`, `var(--spacing-xl)`, `var(--spacing-2xl)`

### Colors
- Background: `var(--bg-primary)`, `var(--bg-card)`
- Text: `var(--text-primary)`, `var(--text-secondary)`, `var(--text-tertiary)`
- Borders: `var(--border-light)`, `var(--border-medium)`

### Border Radius
- Small: `var(--radius-sm)` (8px)
- Medium: `var(--radius-md)` (12px)
- Large: `var(--radius-lg)` (16px)
- Extra Large: `var(--radius-xl)` (24px)

### Shadows
- Small: `var(--shadow-sm)`
- Medium: `var(--shadow-md)`
- Large: `var(--shadow-lg)`

### Typography
- Font sizes: `var(--font-size-xs)` through `var(--font-size-3xl)`
- Use system fonts for native feel

## Responsive Breakpoints

- **Mobile**: < 768px (single column, cards)
- **Tablet**: 768px - 959px (cards, some columns)
- **Desktop**: >= 960px (tables, multi-column)

## Icon Colors by Type

- **Students**: Purple (`rgba(156, 39, 176, 0.1)`)
- **Teachers**: Blue (`rgba(33, 150, 243, 0.1)`)
- **Classes**: Blue (`rgba(33, 150, 243, 0.1)`)
- **Payments**: Green (`rgba(76, 175, 80, 0.1)`)
- **Results**: Orange (`rgba(255, 152, 0, 0.1)`)
- **Attendance**: Teal (`rgba(0, 150, 136, 0.1)`)

## Pages Updated

### ✅ Completed
- DashboardPage
- NotificationsPage
- StudentsListPage
- ClassesListPage
- TeachersListPage
- SubjectsListPage
- PaymentsListPage
- ProfilePage
- MyChildrenPage (Parent)

### 🔄 To Update
- StudentDetailPage
- TeacherDetailPage
- ClassDetailPage
- FeesListPage
- AssessmentsListPage
- ExamsListPage
- TermsListPage
- ResultsPage
- AttendancePage
- All Create/Edit pages
- All other list pages

## Implementation Checklist

For each page update:
- [ ] Replace header with MobilePageHeader
- [ ] Add mobile card view for list pages
- [ ] Keep desktop table view (hidden on mobile)
- [ ] Use MobileCard for detail sections
- [ ] Apply consistent spacing and typography
- [ ] Add loading states with skeletons
- [ ] Add empty states
- [ ] Test on mobile and desktop
- [ ] Ensure touch targets are at least 40px

## Notes

- Always provide both mobile and desktop views
- Use responsive display classes (mobile-list-view, desktop-table-view)
- Maintain consistent spacing and styling
- Use design tokens from CSS variables
- Follow the established patterns for consistency

