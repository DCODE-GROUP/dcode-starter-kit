<template>
  <div class="flex min-h-0 flex-1 flex-col-reverse gap-4 overflow-hidden 2xl:grid 2xl:grid-cols-[minmax(0,1fr)_23.5rem]">
    <div class="flex min-h-0 flex-col overflow-auto">
      <slot name="content" />
    </div>
    <div class="flex min-w-0 flex-col gap-4 overflow-auto">
      <slot name="sidebar" />
    </div>
  </div>
</template>
