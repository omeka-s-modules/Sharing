document.addEventListener("DOMContentLoaded", function () {
    document.querySelector('.sharing-module__embed-button').addEventListener('click', function (e) {
        embedUrl = e.target.dataset.embedUrl;
        embedCode = "<iframe src='" + embedUrl + "'></iframe>";
        alert(embedCode);
    }, {passive: true});
});
