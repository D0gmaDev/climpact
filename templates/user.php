<?php

if (basename($_SERVER["PHP_SELF"]) != "index.php") {
    header("Location:../index.php?view=user");
    die("");
}

include_once("libs/modele.php");

$user = getUserByUsername(valider("username"));

$userEventParticipationId = getUserEventInvolvementIds($user['id'], $type = "participate");
$userEventInterestId = getUserEventInvolvementIds($user['id'], $type = "interested");
$userEventOrganizationId = getUserEventInvolvementIds($user['id'], $type = "orga");


$userParticipate = [];

foreach ($userEventParticipationId as $id) {
    $event = getEventById($id);
    $userParticipate[] = $event;
}

$userBadges = getUserBadges($user['id']) ?? [];

$userInterests = [];

foreach ($userEventInterestId as $id) {
    $userInterests[] = getEventById($id);
}

$userOrga = [];

foreach ($userEventOrganizationId as $id) {
    $event = getEventById($id);
    $userOrga[] = $event;
}

?>

<link rel="stylesheet" href="css/user.css">

<div class="profile-container">
    <h1>Profil</h1>

    <div class="profile-header">
        <div class="profile-photo">
            <img src="<?php echo $user['picture'] ?: "media/default-avatar.png"; ?>" alt="Photo de profil"
                id="profileImg">
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
                    <div class="theme-option default active" onclick="changeTheme('default')" title="Thème par défaut">
                    </div>
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
                $availableBadges = getBadges();

                foreach ($availableBadges as $badgeId => $badge) {
                    $earned = in_array($badge, $userBadges);
                    $earnedClass = $earned ? 'earned' : '';
                    $emoji = $badge['emoji'] ?? '🏅'; // Emoji par défaut si non défini
                    echo "<div class='badge {$earnedClass}' onclick='showBadgeDetail(\"{$badgeId}\")'>";
                    echo "<div class='badge-icon'>{$emoji}</div>";
                    echo "<div class='badge-name'>{$badge['display_name']}</div>";
                    echo "<div class='badge-description'>{$badge['description']}</div>";
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
                                <h4>
                                    <a href="index.php?view=event&event=<?php echo htmlspecialchars($event['id']); ?>"
                                        title="Modifier l'événement : <?php echo htmlspecialchars($event['title']); ?>"
                                        style="color: inherit; text-decoration: none;">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </a>
                                </h4>
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

<script>
    function changeTheme(theme, save=true) {
        document.querySelectorAll('.theme-option').forEach(option => {
            option.classList.remove('active');
        });
        document.querySelector(`.theme-option.${theme}`).classList.add('active');

        if(save){
            console.log('Thème changé vers:', theme);
        }

        var profileImg = document.getElementById('profileImg');

        switch (theme) {
            case 'ocean':
                profileImg.style.borderColor = '#0077be';
                break;
            case 'sunset':
                profileImg.style.borderColor = '#ff6b35';
                break;
            case 'forest':
                profileImg.style.borderColor = '#228b22';
                break;
            default:
                profileImg.style.borderColor = '#2c5f2d';
                break;
        }
    }
</script>