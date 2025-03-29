document.addEventListener('DOMContentLoaded', (event) => {
    document.querySelectorAll('.comment a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const url = new URL(e.target.href);
            const domain = url.hostname;
            document.getElementById('domainString').value = domain;
            document.getElementById('banDomain').checked = true;
        });
    });
});
