document.addEventListener('DOMContentLoaded', function () {
    const commentField = document.getElementById('comment');
    const links = document.querySelectorAll('a[href*="#formPost"]');

    links.forEach(link => {
        link.addEventListener('click', function (event) {
            // Get the current URL path (without fragment)
            const currentPath = window.location.pathname;

            // Get the href attribute (without fragment)
            const linkPath = this.href.split('#')[0];

            // Check if the current URL path matches the link path
            if (currentPath === new URL(linkPath).pathname) {
                event.preventDefault();

                // Extract the post ID from the link's text content or some other identifier
                const postId = this.textContent.trim();

                // Add the quote to the comment field
                if (commentField) {
                    const quoteText = `>>${postId}\n`;
                    commentField.value += quoteText;
                    commentField.focus();
                }

                // Scroll to the form
                const form = document.getElementById('formPost');
                if (form) {
                    form.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
});