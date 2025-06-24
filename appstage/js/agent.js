document.addEventListener("DOMContentLoaded", function () {
    // Fonction de confirmation avant suppression

    // Filtrer les agents par matricule
    document.getElementById('search').addEventListener('input', function () {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#agents-table tbody tr');

        rows.forEach(row => {
            const mat = row.cells[0].textContent.toLowerCase();
            row.style.display = mat.includes(searchValue) ? '' : 'none';
        });
    });



    // Fonction pour afficher un message temporaire
    function showMessage(message, type) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;
        messageDiv.textContent = message;

        // Ajouter le message au début de la page
        document.body.prepend(messageDiv);

        // Supprimer le message après 3 secondes
        setTimeout(() => {
            messageDiv.remove();
        }, 3000);
    }

    // Gestion de la déconnexion
    function logout() {
        const confirmation = confirm("Êtes-vous sûr de vouloir vous déconnecter ?");
        if (confirmation) {
            // Redirection vers la page de déconnexion
            window.location.href = "logout.php";
        }
    }

    // Ajouter un écouteur d'événement pour le bouton de déconnexion
    const logoutButton = document.querySelector('a[href="logout.php"]');
    if (logoutButton) {
        logoutButton.addEventListener('click', function (event) {
            event.preventDefault(); // Empêcher le comportement par défaut du lien
            logout(); // Appeler la fonction de déconnexion
        });
    }
});