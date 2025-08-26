<?php
/*
 * Copyright (c) 2025. Hadrien Sevel
 * Project: forum-rest-api
 * File: AdminController.php
 */

namespace Controller\Api;

use Model\FeatureFlagsModel;
use Model\UserModel;
use Exception;

class AdminController extends BaseController
{
    /**
     * Display the admin dashboard with feature flag management
     * @param string|null $token JWT token for API calls
     * @return void
     */
    public function dashboard(string $token = null): void
    {
        try {
            // Get feature flags
            $featureFlagsModel = new FeatureFlagsModel();
            $result = $featureFlagsModel->getFeatureFlags();
            
            $featureFlags = [];
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $featureFlags[] = $row;
                }
            }
            
            // Get assistants
            $userModel = new UserModel();
            $assistantResult = $userModel->getAssistants();
            
            $assistants = [];
            if ($assistantResult && $assistantResult->num_rows > 0) {
                while ($row = $assistantResult->fetch_assoc()) {
                    $assistants[] = $row;
                }
            }
            
            $this->renderDashboard($featureFlags, $assistants, $token);
        } catch (Exception $e) {
            http_response_code(500);
            echo "Error loading dashboard: " . $e->getMessage();
        }
    }

    /**
     * Toggle a feature flag via AJAX
     * @return void
     */
    public function toggleFeatureFlag(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['name']) || !isset($input['enabled'])) {
                $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Missing required parameters']);
                return;
            }
            
            $featureFlagsModel = new FeatureFlagsModel();
            $affectedRows = $featureFlagsModel->toggleFeatureFlag($input['name'], $input['enabled']);
            
            if ($affectedRows > 0) {
                $this->sendOutput('HTTP/1.1 200 OK', ['success' => true, 'message' => 'Feature flag updated successfully']);
            } else {
                $this->sendOutput('HTTP/1.1 404 Not Found', ['error' => 'Feature flag not found']);
            }
        } catch (Exception $e) {
            $this->sendOutput('HTTP/1.1 500 Internal Server Error', ['error' => $e->getMessage()]);
        }
    }


    /**
     * Toggle endorsed assistant status via AJAX
     * @return void
     */
    public function toggleEndorsedAssistant(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['sciper']) || !isset($input['endorsed'])) {
                $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Missing required parameters']);
                return;
            }
            
            $userModel = new UserModel();
            $affectedRows = $userModel->toggleEndorsedAssistant($input['sciper'], $input['endorsed']);
            
            if ($affectedRows > 0) {
                $this->sendOutput('HTTP/1.1 200 OK', ['success' => true, 'message' => 'Endorsed assistant status updated successfully']);
            } else {
                $this->sendOutput('HTTP/1.1 404 Not Found', ['error' => 'Assistant not found or not eligible']);
            }
        } catch (Exception $e) {
            $this->sendOutput('HTTP/1.1 500 Internal Server Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Add a user as assistant via AJAX
     * @return void
     */
    public function addAssistant(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['sciper']) || !isset($input['email'])) {
                $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'SCIPER and email are required']);
                return;
            }
            
            // Validate SCIPER (should be numeric)
            if (!is_numeric($input['sciper'])) {
                $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'SCIPER must be numeric']);
                return;
            }
            
            // Validate email format
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'Invalid email format']);
                return;
            }
            
            $userModel = new UserModel();
            $affectedRows = $userModel->addUserAsAssistant((int)$input['sciper'], $input['email']);
            
            if ($affectedRows > 0) {
                $this->sendOutput('HTTP/1.1 200 OK', ['success' => true, 'message' => 'User successfully added as assistant']);
            } else {
                $this->sendOutput('HTTP/1.1 500 Internal Server Error', ['error' => 'Failed to add user as assistant']);
            }
        } catch (Exception $e) {
            $this->sendOutput('HTTP/1.1 500 Internal Server Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove assistant and transfer ownership via AJAX
     * @return void
     */
    public function removeAssistant(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['sciper'])) {
                $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'SCIPER is required']);
                return;
            }
            
            // Validate SCIPER (should be numeric)
            if (!is_numeric($input['sciper'])) {
                $this->sendOutput('HTTP/1.1 400 Bad Request', ['error' => 'SCIPER must be numeric']);
                return;
            }
            
            $userModel = new UserModel();
            $affectedRows = $userModel->removeAssistant((int)$input['sciper']);
            
            if ($affectedRows > 0) {
                $this->sendOutput('HTTP/1.1 200 OK', ['success' => true, 'message' => 'Assistant successfully removed and content transferred']);
            } else {
                $this->sendOutput('HTTP/1.1 404 Not Found', ['error' => 'Assistant not found or could not be removed']);
            }
        } catch (Exception $e) {
            $this->sendOutput('HTTP/1.1 500 Internal Server Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Render the admin dashboard HTML
     * @param array $featureFlags
     * @param array $assistants
     * @param string|null $token JWT token for API calls
     * @return void
     */
    private function renderDashboard(array $featureFlags, array $assistants, string $token = null): void
    {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Botafogo Admin Dashboard</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
                    margin: 0;
                    padding: 20px;
                    background-color: #f5f5f5;
                }
                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }
                .header {
                    background: #2563eb;
                    color: white;
                    padding: 20px;
                    text-align: center;
                }
                .content {
                    padding: 20px;
                }
                .flag-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 15px 0;
                    border-bottom: 1px solid #e5e7eb;
                }
                .flag-item:last-child {
                    border-bottom: none;
                }
                .flag-name {
                    font-weight: 600;
                    color: #374151;
                }
                .toggle-switch {
                    position: relative;
                    display: inline-block;
                    width: 60px;
                    height: 34px;
                }
                .toggle-switch input {
                    opacity: 0;
                    width: 0;
                    height: 0;
                }
                .slider {
                    position: absolute;
                    cursor: pointer;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background-color: #ccc;
                    transition: .4s;
                    border-radius: 34px;
                }
                .slider:before {
                    position: absolute;
                    content: "";
                    height: 26px;
                    width: 26px;
                    left: 4px;
                    bottom: 4px;
                    background-color: white;
                    transition: .4s;
                    border-radius: 50%;
                }
                input:checked + .slider {
                    background-color: #10b981;
                }
                input:checked + .slider:before {
                    transform: translateX(26px);
                }
                .status {
                    margin: 20px 0;
                    padding: 10px;
                    border-radius: 4px;
                    display: none;
                }
                .status.success {
                    background-color: #d1fae5;
                    color: #065f46;
                    border: 1px solid #a7f3d0;
                }
                .status.error {
                    background-color: #fee2e2;
                    color: #991b1b;
                    border: 1px solid #fecaca;
                }
                .no-flags {
                    text-align: center;
                    color: #6b7280;
                    font-style: italic;
                    padding: 40px 0;
                }
                .section-title {
                    font-size: 1.25rem;
                    font-weight: 700;
                    color: #374151;
                    margin: 30px 0 15px 0;
                    padding-bottom: 8px;
                    border-bottom: 2px solid #e5e7eb;
                }
                .section-title:first-child {
                    margin-top: 0;
                }
                .user-item {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 15px 0;
                    border-bottom: 1px solid #e5e7eb;
                }
                .user-item:last-child {
                    border-bottom: none;
                }
                .user-info {
                    flex: 1;
                }
                .user-email {
                    font-weight: 600;
                    color: #374151;
                }
                .user-sciper {
                    font-size: 0.875rem;
                    color: #6b7280;
                    margin-top: 2px;
                }
                .endorsed-badge {
                    background-color: #10b981;
                    color: white;
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 0.75rem;
                    font-weight: 500;
                    margin-left: 10px;
                }
                .add-assistant-form .form-row {
                    display: grid;
                    grid-template-columns: 1fr 2fr auto;
                    gap: 20px;
                    align-items: end;
                }
                .add-assistant-form .form-group {
                    display: flex;
                    flex-direction: column;
                }
                .add-assistant-form .button-group {
                    justify-content: center;
                    align-items: flex-end;
                    margin-top: 20px;
                }
                .add-assistant-form button:hover {
                    background: #047857 !important;
                }
                .toggle-switch.disabled {
                    pointer-events: none;
                }
                .toggle-switch.disabled .slider {
                    background-color: #d1d5db !important;
                }
                .toggle-switch.disabled input:checked + .slider {
                    background-color: #d1d5db !important;
                }
                @media (max-width: 768px) {
                    .add-assistant-form .form-row {
                        grid-template-columns: 1fr;
                        gap: 15px;
                    }
                    .add-assistant-form .button-group {
                        margin-top: 5px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Botafogo Admin Dashboard</h1>
                </div>
                <div class="content">
                    <div id="status" class="status"></div>
                    
                    <!-- Feature Flags Section -->
                    <div class="section-title">Feature Flags</div>
                    <?php if (empty($featureFlags)): ?>
                        <div class="no-flags">
                            Pas de "feature flags" dans la base de données.
                        </div>
                    <?php else: ?>
                        <?php foreach ($featureFlags as $flag): ?>
                            <div class="flag-item">
                                <span class="flag-name"><?= htmlspecialchars($flag['name']) ?></span>
                                <label class="toggle-switch">
                                    <input type="checkbox" 
                                           <?= $flag['enabled'] ? 'checked' : '' ?>
                                           onchange="toggleFlag('<?= htmlspecialchars($flag['name']) ?>', this.checked)">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <!-- Assistant Management Section -->
                    <div class="section-title">Management des assistant·es</div>
                    
                    <!-- Add Assistant Form -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e5e7eb;">
                        <h4 style="margin: 0 0 20px 0; color: #374151; font-size: 1.1rem;">Ajouter un·e assistant·e</h4>
                        <div class="add-assistant-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="sciper-input" style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151; font-size: 0.9rem;">SCIPER</label>
                                    <input type="number" id="sciper-input" placeholder="123456" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div class="form-group">
                                    <label for="email-input" style="display: block; font-weight: 600; margin-bottom: 8px; color: #374151; font-size: 0.9rem;">Email</label>
                                    <input type="email" id="email-input" placeholder="prenom.nom@epfl.ch" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                </div>
                                <div class="form-group button-group">
                                    <button onclick="addAssistant()" style="background: #059669; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 14px; min-width: 120px; transition: background-color 0.2s;">Ajouter</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (empty($assistants)): ?>
                        <div class="no-flags">
                            Pas d'assistant·e trouvé·e dans la base de données.
                        </div>
                    <?php else: ?>
                        <?php foreach ($assistants as $assistant): ?>
                            <?php $isSuperUser = in_array($assistant['sciper'], [1, 2]); ?>
                            <div class="user-item">
                                <div class="user-info">
                                    <div class="user-email">
                                        <?= htmlspecialchars($assistant['email']) ?>
                                        <?php if ($isSuperUser): ?>
                                            <span style="color: #6b7280; font-size: 0.8rem; margin-left: 8px;">(Super utilisateur)</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="user-sciper">SCIPER: <?= htmlspecialchars($assistant['sciper']) ?></div>
                                </div>
                                <div style="display: flex; align-items: center;">
                                    <?php if ($assistant['endorsed_assistant']): ?>
                                        <span class="endorsed-badge">Super assistant·e</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($isSuperUser): ?>
                                        <!-- Disabled toggle for super users -->
                                        <label class="toggle-switch disabled" style="margin-left: 10px; opacity: 0.5; cursor: not-allowed;" title="Les super utilisateurs ne peuvent pas être modifiés">
                                            <input type="checkbox" 
                                                   <?= $assistant['endorsed_assistant'] ? 'checked' : '' ?>
                                                   disabled>
                                            <span class="slider"></span>
                                        </label>
                                        <!-- No delete button for super users -->
                                        <span style="color: #6b7280; font-size: 12px; margin-left: 10px; padding: 6px 40px;"></span>
                                    <?php else: ?>
                                        <!-- Normal toggle for regular assistants -->
                                        <label class="toggle-switch" style="margin-left: 10px;">
                                            <input type="checkbox" 
                                                   <?= $assistant['endorsed_assistant'] ? 'checked' : '' ?>
                                                   onchange="toggleEndorsed(<?= $assistant['sciper'] ?>, this.checked)">
                                            <span class="slider"></span>
                                        </label>
                                        <!-- Delete button for regular assistants -->
                                        <button onclick="removeAssistant(<?= $assistant['sciper'] ?>, '<?= htmlspecialchars($assistant['email']) ?>', <?= $assistant['endorsed_assistant'] ? 'true' : 'false' ?>)" 
                                                style="background: #dc2626; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; margin-left: 10px;">
                                            Supprimer
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                // JWT Token for API calls
                const jwtToken = <?= $token ? json_encode($token) : 'null' ?>;
                
                function toggleFlag(name, enabled) {
                    const statusDiv = document.getElementById('status');
                    
                    const headers = {
                        'Content-Type': 'application/json',
                    };
                    
                    // Add Authorization header if token is available
                    if (jwtToken) {
                        headers['Authorization'] = 'Bearer ' + jwtToken;
                    }
                    
                    fetch('<?= BASE_URL ?>/admin/toggle-feature-flag', {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({
                            name: name,
                            enabled: enabled
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showStatus('Feature flag "' + name + '" ' + (enabled ? 'activé' : 'désactivé'), 'success');
                        } else {
                            showStatus('Error: ' + (data.error || 'Erreur inconnue'), 'error');
                            // Revert the toggle if there was an error
                            document.querySelector(`input[onchange*="${name}"]`).checked = !enabled;
                        }
                    })
                    .catch(error => {
                        showStatus('Error: ' + error.message, 'error');
                        // Revert the toggle if there was an error
                        document.querySelector(`input[onchange*="${name}"]`).checked = !enabled;
                    });
                }
                
                function toggleEndorsed(sciper, endorsed) {
                    const statusDiv = document.getElementById('status');
                    
                    const headers = {
                        'Content-Type': 'application/json',
                    };
                    
                    // Add Authorization header if token is available
                    if (jwtToken) {
                        headers['Authorization'] = 'Bearer ' + jwtToken;
                    }
                    
                    fetch('<?= BASE_URL ?>/admin/toggle-endorsed-assistant', {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({
                            sciper: sciper,
                            endorsed: endorsed
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showStatus('Super assistant·e ' + (endorsed ? 'activé' : 'désactivé') + ' pour SCIPER ' + sciper, 'success');
                            // Update the badge visibility
                            const userItem = document.querySelector(`input[onchange*="${sciper}"]`).closest('.user-item');
                            const badge = userItem.querySelector('.endorsed-badge');
                            if (endorsed && !badge) {
                                // Add badge
                                const badgeSpan = document.createElement('span');
                                badgeSpan.className = 'endorsed-badge';
                                badgeSpan.textContent = 'Super assistant·e';
                                userItem.querySelector('div[style*="display: flex"]').insertBefore(badgeSpan, userItem.querySelector('.toggle-switch'));
                            } else if (!endorsed && badge) {
                                // Remove badge
                                badge.remove();
                            }
                        } else {
                            showStatus('Error: ' + (data.error || 'Erreur inconnue'), 'error');
                            // Revert the toggle if there was an error
                            document.querySelector(`input[onchange*="${sciper}"]`).checked = !endorsed;
                        }
                    })
                    .catch(error => {
                        showStatus('Erreur: ' + error.message, 'error');
                        // Revert the toggle if there was an error
                        document.querySelector(`input[onchange*="${sciper}"]`).checked = !endorsed;
                    });
                }
                
                function addAssistant() {
                    const sciper = document.getElementById('sciper-input').value;
                    const email = document.getElementById('email-input').value;
                    const statusDiv = document.getElementById('status');
                    
                    // Validation
                    if (!sciper || !email) {
                        showStatus('Veuillez remplir le SCIPER et l\'email.', 'error');
                        return;
                    }
                    
                    if (!/^\d+$/.test(sciper)) {
                        showStatus('Le SCIPER doit être numérique.', 'error');
                        return;
                    }
                    
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        showStatus('Format d\'email invalide.', 'error');
                        return;
                    }
                    
                    const headers = {
                        'Content-Type': 'application/json',
                    };
                    
                    // Add Authorization header if token is available
                    if (jwtToken) {
                        headers['Authorization'] = 'Bearer ' + jwtToken;
                    }
                    
                    fetch('<?= BASE_URL ?>/admin/add-assistant', {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({
                            sciper: parseInt(sciper),
                            email: email
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showStatus('Assistant·e ajouté·e avec succès!', 'success');
                            // Clear the form
                            document.getElementById('sciper-input').value = '';
                            document.getElementById('email-input').value = '';
                            // Reload the page to show the new assistant
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showStatus('Erreur: ' + (data.error || 'Erreur inconnue'), 'error');
                        }
                    })
                    .catch(error => {
                        showStatus('Erreur: ' + error.message, 'error');
                    });
                }
                
                function removeAssistant(sciper, email, isEndorsed) {
                    const confirmMessage = isEndorsed 
                        ? `Êtes-vous sûr de vouloir supprimer l'assistant·e ${email} (SCIPER: ${sciper})?\n\nCette action va :\n- Supprimer l'assistant·e de la base de données\n- Transférer toutes ses questions et réponses au super utilisateur "Super Assistant·e"\n\nCette action est irréversible.`
                        : `Êtes-vous sûr de vouloir supprimer l'assistant·e ${email} (SCIPER: ${sciper})?\n\nCette action va :\n- Supprimer l'assistant·e de la base de données\n- Transférer toutes ses questions et réponses au super utilisateur "Assistant·e"\n\nCette action est irréversible.`;
                    
                    if (!confirm(confirmMessage)) {
                        return;
                    }
                    
                    const headers = {
                        'Content-Type': 'application/json',
                    };
                    
                    // Add Authorization header if token is available
                    if (jwtToken) {
                        headers['Authorization'] = 'Bearer ' + jwtToken;
                    }
                    
                    fetch('<?= BASE_URL ?>/admin/remove-assistant', {
                        method: 'POST',
                        headers: headers,
                        body: JSON.stringify({
                            sciper: sciper
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showStatus('Assistant·e supprimé·e avec succès!', 'success');
                            // Reload the page to update the assistant list
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showStatus('Erreur: ' + (data.error || 'Erreur inconnue'), 'error');
                        }
                    })
                    .catch(error => {
                        showStatus('Erreur: ' + error.message, 'error');
                    });
                }
                
                function showStatus(message, type) {
                    const statusDiv = document.getElementById('status');
                    statusDiv.textContent = message;
                    statusDiv.className = 'status ' + type;
                    statusDiv.style.display = 'block';
                    
                    setTimeout(() => {
                        statusDiv.style.display = 'none';
                    }, 5000);
                }
            </script>
        </body>
        </html>
        <?php
    }

}