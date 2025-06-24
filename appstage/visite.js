 // Préparer les données pour le graphique
 const groupedData = {};
motifsParAnnee.forEach(item => {
    const annee = item.annee;
    const motif = item.motif;
    const total = item.total;

    if (!groupedData[annee]) {
        groupedData[annee] = {};
    }
    groupedData[annee][motif] = total;
});

// Extraire les années et les motifs uniques
const annees = Object.keys(groupedData);
const motifs = [...new Set(motifsParAnnee.map(item => item.motif))];

// Construire les datasets pour chaque motif
const datasets = motifs.map(motif => ({
    label: motif,
    data: annees.map(annee => groupedData[annee][motif] || 0),
    backgroundColor: `#${Math.floor(Math.random() * 16777215).toString(16)}`, // Couleurs aléatoires
}));

// Labels (années)
const labelsMotifsAnnee = annees;

// Créer le graphique
const ctxMotifsAnnee = document.getElementById('chart-motifs-annee').getContext('2d');
new Chart(ctxMotifsAnnee, {
    type: 'bar',
    data: {
        labels: labelsMotifsAnnee,
        datasets: datasets,
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'top' },
            title: { display: true, text: 'Motifs par Année' },
        },
        scales: {
            x: { title: { display: true, text: 'Années' } },
            y: { title: { display: true, text: 'Total' }, beginAtZero: true },
        },
    },
});

document.getElementById("search").addEventListener("click", function () {
 const matricule = document.getElementById("matricule").value;

 if (matricule) {
     // Envoyer une requête GET avec le matricule
     fetch(`?matricule=${matricule}`)
         .then(response => response.json())
         .then(data => {
             if (data.status === "success") {
                 // Afficher le nom et prénom récupérés
                 document.getElementById("Nom").value = data.nom_prenom;
             } else {
                 // Effacer le champ nom en cas d'erreur
                 document.getElementById("Nom").value = "";
                 alert(data.message);
             }
         })
         .catch(error => {
             console.error("Erreur :", error);
             alert("Une erreur est survenue lors de la recherche.");
         });
 } else {
     alert("Veuillez entrer un matricule.");
 }
});

function logout() {
    const confirmation = confirm("Êtes-vous sûr de vouloir vous déconnecter ?");
    if (confirmation) {
        // Redirection vers la page login.php
        window.location.href = "login.php";
    }
} 