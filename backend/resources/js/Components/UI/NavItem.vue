<template>
  <component
    :is="href ? 'a' : 'button'"
    v-bind="href ? { href, onClick: handleClick } : {}"
    class="nav-item"
    :class="{ active }"
    :title="label"
  >
    <span class="nav-item-icon">
      <component :is="iconComponent" />
    </span>
    <span class="nav-item-label">{{ label }}</span>
    <span
      v-if="badge && badge > 0"
      class="nav-badge"
      :class="`nav-badge--${badgeType}`"
    >{{ badge > 99 ? '99+' : badge }}</span>
  </component>
</template>

<script setup>
import { computed } from 'vue'
import { router } from '@inertiajs/vue3'

// Icon imports
import DashboardIcon  from '@/Components/Icons/DashboardIcon.vue'
import ProductsIcon   from '@/Components/Icons/ProductsIcon.vue'
import SuppliersIcon  from '@/Components/Icons/SuppliersIcon.vue'
import CategoriesIcon from '@/Components/Icons/CategoriesIcon.vue'
import OrdersIcon     from '@/Components/Icons/OrdersIcon.vue'
import StockIcon      from '@/Components/Icons/StockIcon.vue'
import FinancialIcon  from '@/Components/Icons/FinancialIcon.vue'
import ShippingIcon   from '@/Components/Icons/ShippingIcon.vue'
import ApiIcon        from '@/Components/Icons/ApiIcon.vue'
import EmployeesIcon  from '@/Components/Icons/EmployeesIcon.vue'
import CouponsIcon    from '@/Components/Icons/CouponsIcon.vue'
import PointsIcon     from '@/Components/Icons/PointsIcon.vue'
import ReferralsIcon  from '@/Components/Icons/ReferralsIcon.vue'
import ImportIcon     from '@/Components/Icons/ImportIcon.vue'
import SecurityIcon   from '@/Components/Icons/SecurityIcon.vue'
import CalendarIcon   from '@/Components/Icons/CalendarIcon.vue'
import StarIcon       from '@/Components/Icons/StarIcon.vue'
import AnalyticsIcon  from '@/Components/Icons/AnalyticsIcon.vue'
import ImageIcon      from '@/Components/Icons/ImageIcon.vue'

const props = defineProps({
  href:      { type: String,  default: null },
  icon:      { type: String,  required: true },
  label:     { type: String,  required: true },
  active:    { type: Boolean, default: false },
  badge:     { type: Number,  default: null },
  badgeType: { type: String,  default: 'danger' }, // danger | warning
})

const iconMap = {
  dashboard:  DashboardIcon,
  products:   ProductsIcon,
  suppliers:  SuppliersIcon,
  categories: CategoriesIcon,
  orders:     OrdersIcon,
  stock:      StockIcon,
  financial:  FinancialIcon,
  shipping:   ShippingIcon,
  api:        ApiIcon,
  employees:  EmployeesIcon,
  coupons:    CouponsIcon,
  points:     PointsIcon,
  referrals:  ReferralsIcon,
  import:     ImportIcon,
  security:   SecurityIcon,
  calendar:   CalendarIcon,
  star:       StarIcon,
  analytics:  AnalyticsIcon,
  image:      ImageIcon,
}

const iconComponent = computed(() => iconMap[props.icon] || DashboardIcon)

function handleClick(e) {
  e.preventDefault()
  if (props.href) router.visit(props.href)
}
</script>

<style scoped>
.nav-item-label {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.nav-badge--warning {
  background: var(--color-warning);
  color: #fff;
}
</style>
