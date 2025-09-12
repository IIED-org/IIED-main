document.addEventListener('alpine:init', () => {
  Alpine.store('showSearch', false),
  Alpine.store('searchHeader', true)
})