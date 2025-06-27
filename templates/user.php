<?php

if (basename($_SERVER["PHP_SELF"]) != "index.php") {
	header("Location:../index.php?view=user");
	die("");
}

include_once("libs/modele.php");
include_once("libs/maLibUtils.php"); // tprint
include_once("libs/maLibForms.php"); // mkTable, mkSelect

// Récupération des données additionnelles pour le profil
// À implémenter selon votre base de données

$user = getUserByUsername(valider("username"));

$userEventParticipationId = getUserEventInvolvementIds($user['id'], $type = "participate");
$userEventInterestId = getUserEventInvolvementIds($user['id'], $type = "interested");
$userEventOrganizationId = getUserEventInvolvementIds($user['id'], $type = "organize");



$userParticipate = [];

foreach ($userEventParticipationId as $id) {
    $userParticipate[] = getEvent($id);
}

$userBadges = getUserBadges($user['id']) ?? [];

$userInterests = [];

foreach ($userEventInterestId as $id) {
    $userInterests[] = getEvent($id);
}

$userOrga = [];

foreach ($userEventOrganizationId as $id) {
    $userOrga[] = getEvent($id);
}

?>

<style>
.profile-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.profile-header {
    background: white;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 30px;
}

.profile-photo {
    position: relative;
}

.profile-photo img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #2c5f2d;
}

.photo-edit-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    background: #2c5f2d;
    color: white;
    border: none;
    border-radius: 50%;
    width: 32px;
    height: 32px;
    cursor: pointer;
    font-size: 16px;
}

.profile-info h2 {
    color: #2c5f2d;
    margin-bottom: 10px;
}

.role-badge {
    background: #e8f5e8;
    color: #2c5f2d;
    padding: 5px 15px;
    border-radius: 16px;
    font-size: 14px;
    display: inline-block;
    margin-bottom: 15px;
}

.theme-selector {
    margin-top: 15px;
}

.theme-options {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.theme-option {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
}

.theme-option.active {
    border-color: #2c5f2d;
}

.theme-option.default { background: linear-gradient(135deg, #2c5f2d, #4a8f4f); }
.theme-option.ocean { background: linear-gradient(135deg, #0077be, #00a8cc); }
.theme-option.sunset { background: linear-gradient(135deg, #ff6b35, #f7931e); }
.theme-option.forest { background: linear-gradient(135deg, #228b22, #32cd32); }

.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.section {
    background: white;
    border-radius: 8px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.section h3 {
    color: #2c5f2d;
    margin-bottom: 15px;
    font-size: 20px;
}

.badges-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 15px;
}

.badge {
    background: #f8f9fa;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.badge:hover {
    border-color: #2c5f2d;
    transform: translateY(-2px);
}

.badge.earned {
    background: #e8f5e8;
    border-color: #2c5f2d;
}

.badge-icon {
    font-size: 32px;
    margin-bottom: 8px;
}

.badge-name {
    font-weight: bold;
    margin-bottom: 4px;
}

.badge-description {
    font-size: 14px;
    color: #666;
}

.event-list {
    max-height: 400px;
    overflow-y: auto;
}

.event-item {
    border-bottom: 1px solid #e9ecef;
    padding: 15px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.event-item:last-child {
    border-bottom: none;
}

.event-info h4 {
    color: #2c5f2d;
    margin-bottom: 4px;
}

.event-date {
    font-size: 14px;
    color: #666;
}

.event-type {
    background: #2c5f2d;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.event-type.organized {
    background: #ff6b35;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    background: white;
    margin: 10% auto;
    padding: 30px;
    border-radius: 8px;
    max-width: 500px;
    position: relative;
}

.close-btn {
    position: absolute;
    top: 15px;
    right: 15px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

@media (max-width: 768px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .badges-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="profile-container">
    <h1>Mon Profil</h1>
    
    <div class="profile-header">
        <div class="profile-photo">
            <img src="<?php echo $user['picture'] ?: "media/default-avatar.png"; ?>" 
                 alt="Photo de profil" id="profileImg">
            <button class="photo-edit-btn" onclick="openPhotoModal()">📷</button>
        </div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?></h2>
            <div class="role-badge">
                <?php 
                $roleDisplay = ucfirst($user['role'] ?? 'Étudiant');
                if (isset($user['cursus']) && $user['cursus']) {
                    $roleDisplay .= ' - ' . htmlspecialchars($user['cursus']);
                }
                echo $roleDisplay;
                ?>
            </div>
            <?php if (isset($user['promotion'])): ?>
                <p><strong>Promotion :</strong> <?php echo htmlspecialchars($user['promotion']); ?></p>
            <?php endif; ?>
            <?php if (isset($user['school'])): ?>
                <p><strong>École :</strong> <?php echo htmlspecialchars($user['school']); ?></p>
            <?php endif; ?>
            
            <div class="theme-selector">
                <label><strong>Thème du profil :</strong></label>
                <div class="theme-options">
                    <div class="theme-option default active" onclick="changeTheme('default')" title="Thème par défaut"></div>
                    <div class="theme-option ocean" onclick="changeTheme('ocean')" title="Océan"></div>
                    <div class="theme-option sunset" onclick="changeTheme('sunset')" title="Coucher de soleil"></div>
                    <div class="theme-option forest" onclick="changeTheme('forest')" title="Forêt"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="content-grid">
        <div class="section">
            <h3>🏆 Mes Badges</h3>
            <div class="badges-grid">
                <?php
                // Définition des badges disponibles
                $availableBadges = [
                    'nouveau' => ['icon' => '🎯', 'name' => 'Nouveau venu', 'desc' => 'C\'est parti pour l\'engagement !'],
                    'curieux' => ['icon' => '🧩', 'name' => 'Curieux.se', 'desc' => 'Toujours à l\'affût des bonnes initiatives.'],
                    'actif' => ['icon' => '💬', 'name' => 'Actif.ve', 'desc' => 'Engagé.e dans l\'action !'],
                    'super' => ['icon' => '💥', 'name' => 'Super participant.e', 'desc' => 'Pilier des événements CLimpact.'],
                    'organisateur' => ['icon' => '🛠', 'name' => 'Organisateur.rice', 'desc' => 'Tu lances les initiatives, bravo !'],
                    'fidele' => ['icon' => '🔁', 'name' => 'Fidèle', 'desc' => 'L\'engagement, c\'est dans la durée.']
                ];
                
                foreach ($availableBadges as $badgeId => $badge) {
                    $earned = in_array($badgeId, $userBadges);
                    $earnedClass = $earned ? 'earned' : '';
                    echo "<div class='badge {$earnedClass}' onclick='showBadgeDetail(\"{$badgeId}\")'>";
                    echo "<div class='badge-icon'>{$badge['icon']}</div>";
                    echo "<div class='badge-name'>{$badge['name']}</div>";
                    echo "<div class='badge-description'>{$badge['desc']}</div>";
                    echo "</div>";
                }
                ?>
            </div>
        </div>

        <div class="section">
            <h3>📅 Événements auxquels j'ai participé</h3>
            <div class="event-list">
                <?php if (empty($userParticipate)): ?>
                    <div class="event-item">
                        <div class="event-info">
                            <h4>Aucun événement</h4>
                            <div class="event-date">Participez à des événements pour les voir apparaître ici !</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php tprint($userParticipate)?>
                    <?php foreach ($userParticipate as $event): ?>
                        <div class="event-item">
                            <div class="event-info">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <div class="event-date"><?php echo date('d/m/Y', strtotime($event['start_time'])); ?></div>
                            </div>
                            <div class="event-type">Participé</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h3>📝 Événements que j'ai organisés</h3>
            <div class="event-list">
                <?php if (empty($userOrga)): ?>
                    <div class="event-item">
                        <div class="event-info">
                            <h4>Aucun événement organisé</h4>
                            <div class="event-date">Commencez à organiser des événements pour apparaître ici !</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($userOrga as $event): ?>
                        <div class="event-item">
                            <div class="event-info">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <div class="event-date"><?php echo date('d/m/Y', strtotime($event['start_time'])); ?></div>
                            </div>
                            <div class="event-type organized">Organisé</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h3>⭐ Événements qui m'intéressent</h3>
            <div class="event-list">
                <?php if (empty($userInterests)): ?>
                    <div class="event-item">
                        <div class="event-info">
                            <h4>Aucun événement d'intérêt</h4>
                            <div class="event-date">Marquez des événements comme intéressants pour les retrouver ici !</div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php tprint($userInterests)?>
                    <?php foreach ($userInterests as $event): ?>
                        <div class="event-item">
                            <div class="event-info">
                                <h4><?php echo htmlspecialchars($event['title']); ?></h4>
                                <div class="event-date"><?php echo date('d/m/Y', strtotime($event['start_time'])); ?></div>
                            </div>
                            <div class="event-type">Intéressé</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour changer la photo -->
<div id="photoModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closePhotoModal()">&times;</button>
        <h3>Modifier la photo de profil</h3>
        <form id="photoForm" enctype="multipart/form-data">
            <input type="file" id="photoInput" name="profilePhoto" accept="image/*" onchange="previewPhoto()">
            <div id="photoPreview" style="text-align: center; margin: 15px 0;"></div>
            <div style="text-align: right; margin-top: 15px;">
                <button type="button" onclick="closePhotoModal()" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 4px; margin-right: 10px;">Annuler</button>
                <button type="button" onclick="savePhoto()" style="background: #2c5f2d; color: white; border: none; padding: 10px 20px; border-radius: 4px;">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal pour les détails des badges -->
<div id="badgeModal" class="modal">
    <div class="modal-content">
        <button class="close-btn" onclick="closeBadgeModal()">&times;</button>
        <div id="badgeDetail"></div>
    </div>
</div>

<script>
// Données des badges
const badgeData = {
    nouveau: {
        icon: '🎯',
        name: 'Nouveau venu',
        description: 'C\'est parti !',
        criteria: 'Première connexion à CLimpact',
        percentage: '100%'
    },
    curieux: {
        icon: '🧩',
        name: 'Curieux.se',
        description: 'Toujours à l\'affût des bonnes initiatives.',
        criteria: 'S\'être intéressé(e) à 3 événements différents',
        percentage: '76%'
    },
    actif: {
        icon: '💬',
        name: 'Actif.ve', 
        description: 'Engagé.e dans l\'action !',
        criteria: 'Avoir participé à 3 événements',
        percentage: '45%'
    },
    super: {
        icon: '💥',
        name: 'Super participant.e',
        description: 'Pilier des événements CLimpact.',
        criteria: 'Avoir participé à 10 événements',
        percentage: '23%'
    },
    organisateur: {
        icon: '🛠',
        name: 'Organisateur.rice',
        description: 'Tu lances les initiatives, bravo !',
        criteria: 'Avoir organisé au moins 1 événement',
        percentage: '15%'
    },
    fidele: {
        icon: '🔁',
        name: 'Fidèle',
        description: 'L\'engagement, c\'est dans la durée.',
        criteria: 'Avoir participé à des événements sur 3 mois différents',
        percentage: '34%'
    }
};

const userBadges = <?php echo json_encode($userBadges); ?>;

// Gestion des thèmes

// franchement on règlera ça plus tard
function changeTheme(theme) {
    document.querySelectorAll('.theme-option').forEach(option => {
        option.classList.remove('active');
    });
    document.querySelector(`.theme-option.${theme}`).classList.add('active');
    
    // Ici  faire un appel AJAX pour sauvegarder le thème
    console.log('Thème changé vers:', theme);
}

// Gestion de la photo de profil
function openPhotoModal() {
    document.getElementById('photoModal').style.display = 'block';
}

function closePhotoModal() {
    document.getElementById('photoModal').style.display = 'none';
    document.getElementById('photoInput').value = '';
    document.getElementById('photoPreview').innerHTML = '';
}

function previewPhoto() {
    const input = document.getElementById('photoInput');
    const preview = document.getElementById('photoPreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `<img src="${e.target.result}" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">`;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function savePhoto() {
    const input = document.getElementById('photoInput');
    if (input.files && input.files[0]) {
        // Ici faire un appel AJAX pour sauvegarder la photo
        const formData = new FormData();
        formData.append('profilePhoto', input.files[0]);
        
        // code à fair quand on a le temps
        
        // Pour la démo, on màj directement de l'image
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('profileImg').src = e.target.result;
            closePhotoModal();
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Gestion des badges
function showBadgeDetail(badgeId) {
    const badge = badgeData[badgeId];
    const modal = document.getElementById('badgeModal');
    const detail = document.getElementById('badgeDetail');
    const earned = userBadges.includes(badgeId);
    
    detail.innerHTML = `
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="font-size: 64px; margin-bottom: 15px;">${badge.icon}</div>
            <h3>${badge.name}</h3>
            <p style="color: #666; margin-bottom: 15px;">${badge.description}</p>
        </div>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
            <p><strong>Critère :</strong> ${badge.criteria}</p>
            <p><strong>Obtenu par :</strong> ${badge.percentage} des utilisateurs</p>
        </div>
        <div style="text-align: center;">
            ${earned ? 
                '<span style="color: #2c5f2d; font-weight: bold;">✅ Badge obtenu !</span>' : 
                '<span style="color: #666;">🔒 Badge non obtenu</span>'
            }
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeBadgeModal() {
    document.getElementById('badgeModal').style.display = 'none';
}

// Fermer les modals en cliquant à l'extérieur
window.onclick = function(event) {
    const photoModal = document.getElementById('photoModal');
    const badgeModal = document.getElementById('badgeModal');
    
    if (event.target === photoModal) {
        closePhotoModal();
    }
    if (event.target === badgeModal) {
        closeBadgeModal();
    }
}
</script>

