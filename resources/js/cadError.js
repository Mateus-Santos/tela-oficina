document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('remove-button').addEventListener('click', function() {
        var content = document.getElementById('content-to-remove');
        content.parentNode.removeChild(content);
    });
});