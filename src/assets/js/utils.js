function copyToClipboard(element) {
    navigator.clipboard.writeText(element.getAttribute('value'))
}
