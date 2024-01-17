document.getElementById("copy-button").addEventListener("click", function() {
    var copyText = document.getElementById("citation-text");
    // Create a new text area
    var textArea = document.createElement("textarea");
  // Trim spaces & Replace multiple space or line breaks with a single space
    textArea.value = copyText.textContent.trim().replace(/\s\s+/g, ' ');
    // Prevents scrolling to the bottom of the page in MS Edge.
    textArea.style.all = 'unset';
    textArea.style.position = 'fixed';
    textArea.style.top = 0;
    textArea.style.clip = 'rect(0, 0, 0, 0)';
    textArea.style.whiteSpace = 'pre';
    textArea.style.webkitUserSelect = 'text';
    // Adds the text area and selects the text.
    document.body.appendChild(textArea);
    textArea.select(); 

    try {
        document.execCommand('copy');
        // Alert statement removed from here
    } catch (err) {
        console.error('Failed to copy text to clipboard', err);
    } finally {
        document.body.removeChild(textArea);
    }
    // Change the icon after copying
    document.getElementById("copy-button").style.display = "none";
    document.getElementById("copied-icon").style.display = "block";
});
