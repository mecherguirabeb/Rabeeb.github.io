// Données des projets
const projects = [
    {
        title: "Solution Cloud Scalable",
        description: "Développement d'une architecture cloud optimisée pour les applications haute performance avec intégration continue.",
        tags: ["Cloud", "DevOps"],
        image: "https://via.placeholder.com/400x300"
    },
    {
        title: "Application de Productivité",
        description: "Conception et développement d'une application mobile cross-platform avec interface utilisateur minimaliste.",
        tags: ["Mobile", "UI/UX"],
        image: "https://via.placeholder.com/400x300"
    },
    {
        title: "Système de Design",
        description: "Création d'un système de design cohérent pour une suite d'applications d'entreprise.",
        tags: ["Design", "UI Kit"],
        image: "https://via.placeholder.com/400x300"
    }
];

// Données des centres d'intérêt
const interests = [
    {
        title: "Développement personnel",
        description: "Méditation, productivité et croissance continue",
        icon: "fas fa-brain"
    },
    {
        title: "Photographie",
        description: "Photographie créative et storytelling visuel",
        icon: "fas fa-camera"
    },
    {
        title: "Design graphique",
        description: "Création d'interfaces utilisateur et d'identités visuelles",
        icon: "fas fa-pencil-ruler"
    }
];

// Chargement des projets
document.addEventListener('DOMContentLoaded', function() {
    loadProjects();
    loadInterests();
    setupForm();
    setupScrollAnimation();
});

function loadProjects() {
    const container = document.getElementById('projects-container');
    
    projects.forEach(project => {
        const tagsHTML = project.tags.map(tag => 
            `<div class="badge bg-primary me-2">${tag}</div>`
        ).join('');
        
        const projectHTML = `
        <div class="col-lg-4 col-md-6">
            <div class="card project-card h-100">
                <img src="${project.image}" class="card-img-top project-img" alt="${project.title}">
                <div class="card-body">
                    <h5 class="card-title">${project.title}</h5>
                    <p class="card-text">${project.description}</p>
                    ${tagsHTML}
                </div>
            </div>
        </div>
        `;
        
        container.innerHTML += projectHTML;
    });
}

function loadInterests() {
    const container = document.getElementById('interests-container');
    
    interests.forEach(interest => {
        const interestHTML = `
        <div class="col-md-4">
            <div class="interest-item">
                <div class="interest-icon">
                    <i class="${interest.icon}"></i>
                </div>
                <h4>${interest.title}</h4>
                <p>${interest.description}</p>
            </div>
        </div>
        `;
        
        container.innerHTML += interestHTML;
    });
}

function setupForm() {
    const form = document.getElementById('contact-form');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Récupération des valeurs du formulaire
        const name = document.getElementById('name').value;
        const email = document.getElementById('email').value;
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value;
        
        // Ici, vous pourriez ajouter une logique pour envoyer le formulaire
        console.log('Formulaire soumis:', { name, email, subject, message });
        
        // Réinitialisation du formulaire
        form.reset();
        
        // Message de succès
        alert('Merci pour votre message! Je vous répondrai dès que possible.');
    });
}

function setupScrollAnimation() {
    // Configuration de l'animation au défilement
    const revealElements = document.querySelectorAll('.reveal');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
            }
        });
    }, { threshold: 0.1 });
    
    revealElements.forEach(element => {
        observer.observe(element);
    });
    
    // Ajout de la classe reveal aux sections
    document.querySelectorAll('section').forEach((section, index) => {
        if (index > 0) { // On saute la hero section
            section.classList.add('reveal');
        }
    });
}