document.addEventListener('DOMContentLoaded', function() {
    // Führe diese Funktion aus, wenn ein Button geklickt wird
    document.body.addEventListener('click', function(e) {
        if (e.target.matches('.my-favorite-post-inline-button, .my-favorite-post-button, .wprm-recipe-favorite-button')) {
            e.preventDefault();
            var button = e.target;
            var post_id = button.getAttribute('data-post-id');
            var is_wprm = button.getAttribute('data-is-wprm') === 'true';
            
            var formData = new FormData();
            formData.append('action', 'my_favorite_post');
            formData.append('post_id', post_id);
            formData.append('is_wprm', is_wprm ? '1' : '0');

            // AJAX-Anfrage an den Server
            fetch(MyFavoritePosts.ajax_url, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    // Speichern des Like-Status im LocalStorage
                    var likedPosts = JSON.parse(localStorage.getItem('my_favorite_posts')) || [];
                    
                    if (button.classList.contains('liked')) {
                        button.classList.remove('liked');
                        button.textContent = '♡';
                        // Entfernen des Posts aus LocalStorage
                        likedPosts = likedPosts.filter(id => id !== post_id);
                    } else {
                        button.classList.add('liked');
                        button.textContent = '♥';
                        // Hinzufügen des Posts zu LocalStorage
                        if (!likedPosts.includes(post_id)) {
                            likedPosts.push(post_id);
                        }
                    }
                    localStorage.setItem('my_favorite_posts', JSON.stringify(likedPosts));
                } else {
                    console.error('Fehler: ' + response.data);
                }
            })
            .catch(error => {
                console.error('Es gab ein Problem beim Speichern/Entfernen des Beitrags.', error);
            });
        }
    });

    // Beim Laden der Seite den Like-Status wiederherstellen
    var likedPosts = JSON.parse(localStorage.getItem('my_favorite_posts')) || [];
    document.querySelectorAll('.my-favorite-post-inline-button, .my-favorite-post-button, .wprm-recipe-favorite-button').forEach(function(button) {
        var post_id = button.getAttribute('data-post-id');
        if (likedPosts.includes(post_id)) {
            button.classList.add('liked');
            button.textContent = '♥';
        } else {
            button.classList.remove('liked');
            button.textContent = '♡';
        }
    });
});