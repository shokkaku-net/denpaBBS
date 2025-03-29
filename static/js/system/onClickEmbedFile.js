function toggleEmbed(event, element) {
    event.preventDefault();  

    var linkE = event.currentTarget;
    var fileID = linkE.parentNode.getAttribute('id');

    var fileNameE = linkE.parentNode.parentNode.querySelector('#' + fileID + '.fileName');
    var fileE = linkE.parentNode.parentNode.querySelector('#' + fileID + '.file');

    var thumbnailE = fileE.querySelector('img');
    var fullE = fileE.querySelector('.full');

    var closeButtonExists = Array.from(fileNameE.querySelectorAll('a')).some(a => 
        a.textContent.includes('close') && a.getAttribute('href') === '#'
    );
    if (closeButtonExists) {
        return;
    }

    if (fullE) {
        thumbnailE.classList.toggle('hidden');
        fullE.classList.toggle('hidden');
        return;
    }

    fullE = element;

    if (linkE.className !== 'image') {
        // For non-image types, wrap the <a> tag in a <div> and append the element
        var wrapperDiv = document.createElement('div');
        wrapperDiv.className = 'media-wrapper';
        fileE.replaceChild(wrapperDiv, linkE); // Replace the <a> tag with the wrapper <div>
        wrapperDiv.appendChild(linkE); // Re-add the <a> tag inside the wrapper <div>
        wrapperDiv.appendChild(fullE); // Add the embedded media inside the wrapper <div>

        // Hide the thumbnail
        thumbnailE.classList.add('hidden');

        // Create a close hyperlink
        const closeLink = document.createElement('a');
        closeLink.href = '#';
        closeLink.textContent = 'close';
        closeLink.onclick = function (e) {
            e.preventDefault();
            e.stopPropagation();
            fullE.remove(); // Remove the embedded element
            thumbnailE.classList.remove('hidden');  // Show the thumbnail again
            closeLink.parentNode.removeChild(closeLink.previousSibling); // Remove the '['
            closeLink.parentNode.removeChild(closeLink.nextSibling); // Remove the ']'
            closeLink.parentNode.removeChild(closeLink); // Remove the close link itself

            // Restore the original <a> tag
            wrapperDiv.parentNode.replaceChild(linkE, wrapperDiv);
        };

        fileNameE.insertAdjacentHTML("afterbegin", "]");
        fileNameE.insertAdjacentElement("afterbegin", closeLink);
        fileNameE.insertAdjacentHTML("afterbegin", "[");
    } else {
        // For images, embed the element directly without wrapping
        fullE.classList.add('full');
        thumbnailE.classList.toggle('hidden');
        linkE.appendChild(fullE);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    var links = document.querySelectorAll('.file a');
    links.forEach(function(link) {
        link.addEventListener('click', function(event) {
            event.preventDefault(); // Prevent the default action of the <a> tag

            switch (link.className) {
                case 'image':
                    var img = new Image();
                    img.src = link.href;
                    toggleEmbed(event, img);
                    break;
                case 'video':
                    var video = document.createElement('video');
                    video.setAttribute('loading', 'lazy');
                    video.src = link.href;
                    video.controls = true;
                    video.autoplay = true;
                    toggleEmbed(event, video);
                    break;
                case 'swf':
                    var object = document.createElement('object');
                    var embed = document.createElement('embed');
                    embed.src = link.href;
                    object.appendChild(embed);
                    toggleEmbed(event, object);
                    break;
                default:
                    console.log('This file type does not have a predefined embed format.', link);
            }
        });
    });
});
