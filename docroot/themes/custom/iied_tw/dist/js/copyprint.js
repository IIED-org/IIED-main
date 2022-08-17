copyUrl = document.querySelector('#copyUrl');
copyUrl.addEventListener('click', function(event) {
  try {
    let successful = navigator.clipboard.writeText(window.location.href);
    let msg = successful ? 'successful' : 'unsuccessful';
    alert('Copy link command was ' + msg);
  } catch(err) {
    alert('Unable to copy');
  }
});

printPdf = document.querySelector('#printPdf');
printPdf.addEventListener('click', function(event) {
  try {
    window.print();
  } catch(err) {
    alert('Unable to print page');
  }
});
