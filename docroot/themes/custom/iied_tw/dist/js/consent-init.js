//  Define dataLayer and the gtag function
window.dataLayer = window.dataLayer || [];
function gtag() {
  dataLayer.push(arguments);
}
// Set default consent to 'denied' as a placeholder
// Determine actual values based on your own requirements
gtag("consent", "default", {
  ad_storage: "denied",
  ad_user_data: "denied",
  ad_personalization: "denied",
  analytics_storage: "denied",
  wait_for_update: 500,
});
