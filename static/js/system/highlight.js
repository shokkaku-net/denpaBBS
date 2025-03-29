document.addEventListener("DOMContentLoaded", function() {
    // Get all the .file divs
    var fileContainers = document.querySelectorAll('.files');

    fileContainers.forEach(function(container) {
        var files = container.querySelectorAll('.file');

        // Function to find and highlight the corresponding .fileName div
        if (files.length > 1) {
            function highlightFileName(e) {
                // Get the ID of the current .file div
                var fileId = e.currentTarget.id;

                // Find the corresponding .fileName div within the same .files container
                var fileNameDiv = container.querySelector('.fileName#' + fileId);

                // Change the background color to highlight
                if (fileNameDiv) {
                    fileNameDiv.style.backgroundColor = '#eeaa88';
                }
            }
        }

        // Function to reset the background color when mouse leaves
        function resetFileNameHighlight(e) {
            var fileId = e.currentTarget.id;
            var fileNameDiv = container.querySelector('.fileName#' + fileId);
            if (fileNameDiv) {
                fileNameDiv.style.backgroundColor = ''; // Reset to original or transparent
            }
        }

        // Add event listeners to each .file.inLine div
        files.forEach(function(file) {
            file.addEventListener('mouseenter', highlightFileName);
            file.addEventListener('mouseleave', resetFileNameHighlight);
        });
    });
});