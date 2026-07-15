
document.querySelectorAll('.js-copy-url').forEach(function(copyUrl) {
  copyUrl.addEventListener('click', async function(event) {
    try {
      await navigator.clipboard.writeText(window.location.href);
      alert('Link copied to clipboard');
    } catch (err) {
      alert('Unable to copy link');
    }
  });
});

document.querySelectorAll('.js-print-pdf').forEach(function(printPdf) {
  printPdf.addEventListener('click', function(event) {
    try {
      window.print();
    } catch (err) {
      alert('Unable to print page');
    }
  });
});