

// Filtrer les années

function filterYears() {
    const searchInput = document.getElementById('search').value.toLowerCase();
    const yearItems = document.querySelectorAll('.year-item');

    yearItems.forEach(item => {
        const year = item.textContent.toLowerCase();
        if (year.includes(searchInput)) {
            item.style.display = 'list-item';
        } else {
            item.style.display = 'none';
        }
    });
}

// Afficher/masquer les mois d'une année
function toggleMonths(yearId) {
    const monthList = document.getElementById(yearId);
    if (monthList.style.display === "none") {
        monthList.style.display = "block";
    } else {
        monthList.style.display = "none";
    }
}

// Fonction pour obtenir le nom du mois
function getMonthName(month) {
    const monthNames = [
        "Janvier", "Février", "Mars", "Avril", "Mai", "Juin",
        "Juillet", "Août", "Septembre", "Octobre", "Novembre", "Décembre"
    ];
    return monthNames[month - 1]; // Mois est 1-indexed (1-12)
}

// Afficher la modale
function showStats(year) {
    document.getElementById('statsModal').style.display = 'block';
    document.getElementById('modalYear').textContent = year;

    // Charger les statistiques via AJAX
    fetch(`get_stats.php?year=${year}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('modalStats').textContent = `Nombre total de jours : ${data.total_days}`;
        })
        .catch(error => {
            document.getElementById('modalStats').textContent = 'Erreur lors du chargement des statistiques.';
        });
}


function toggleYearDetails(year) {
    const yearList = document.getElementById(`year-${year}`);
    if (yearList.style.display === "none") {
        yearList.style.display = "block";
    } else {
        yearList.style.display = "none";
    }
}

function logout() {
    const confirmation = confirm("Êtes-vous sûr de vouloir vous déconnecter ?");
    if (confirmation) {
        // Redirection vers la page login.html
        window.location.href = "login.html";
    }
}  

document.addEventListener('DOMContentLoaded', function () {
    const searchButton = document.getElementById('search');

    searchButton.addEventListener('click', function (e) {
        e.preventDefault(); // Empêche le rechargement de la page

        const matricule = document.getElementById('matricule').value.trim();

        if (matricule) {
            fetch(`index.php?matricule=${encodeURIComponent(matricule)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('nom_prenom').value = data.nom_prenom;
                        document.getElementById('fonction').value = data.fonction;
                    } else {
                        // Réinitialise les champs si aucune donnée n'est trouvée
                        document.getElementById('nom_prenom').value = '';
                        document.getElementById('fonction').value = '';
                        alert(data.message || 'Aucun résultat trouvé.');
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la requête :', error);
                    alert('Une erreur est survenue. Veuillez réessayer.');
                });
        } else {
            alert('Veuillez entrer un matricule.');
        }
    });
});


























