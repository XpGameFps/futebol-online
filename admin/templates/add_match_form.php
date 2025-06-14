<?php
// admin/templates/add_match_form.php

// Handle form error messages for 'add_match'
$add_match_form_error_html = '';
if (isset($_SESSION['form_error_message']['add_match'])) {
    $add_match_form_error_html = '<div class="message"><p style="color:red; background-color: #f8d7da; border:1px solid #f5c6cb; padding:10px; border-radius:4px;">' . htmlspecialchars($_SESSION['form_error_message']['add_match']) . '</p></div>';
    unset($_SESSION['form_error_message']['add_match']);
}

// Retrieve and clear form data for 'add_match'
$form_data_add_match = $_SESSION['form_data']['add_match'] ?? [];
if (isset($_SESSION['form_data']['add_match'])) {
    unset($_SESSION['form_data']['add_match']);
}

// $csrf_token, $leagues_for_dropdown, $teams_for_dropdown are expected to be in scope from admin/index.php
?>
<h2 id="add-match-form">Adicionar Novo Jogo</h2>
<?php if (!empty($add_match_form_error_html)) echo $add_match_form_error_html; ?>

<form action="add_match.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

    <fieldset>
        <legend>Informações da Partida</legend>
        <div class="form-row">
            <div class="form-group-inline">
                <label for="home_team_id_form">Time da Casa:</label>
                <select id="home_team_id_form" name="home_team_id" required>
                    <option value="">-- Selecionar Time da Casa --</option>
                    <?php foreach ($teams_for_dropdown as $team_opt): ?>
                        <option value="<?php echo htmlspecialchars($team_opt['id']); ?>" <?php echo (isset($form_data_add_match['home_team_id']) && $form_data_add_match['home_team_id'] == $team_opt['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($team_opt['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group-inline">
                <label for="away_team_id_form">Time Visitante:</label>
                <select id="away_team_id_form" name="away_team_id" required>
                    <option value="">-- Selecionar Time Visitante --</option>
                    <?php foreach ($teams_for_dropdown as $team_opt): ?>
                        <option value="<?php echo htmlspecialchars($team_opt['id']); ?>" <?php echo (isset($form_data_add_match['away_team_id']) && $form_data_add_match['away_team_id'] == $team_opt['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($team_opt['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group-inline">
                <label for="match_time_form">Data e Hora da Partida:</label>
                <input type="datetime-local" id="match_time_form" name="match_time" value="<?php echo htmlspecialchars($form_data_add_match['match_time'] ?? ''); ?>" required>
            </div>
            <div class="form-group-inline">
                <label for="league_id_form">Liga (Opcional):</label>
                <select id="league_id_form" name="league_id">
                    <option value="">-- Selecionar Liga --</option>
                    <?php foreach ($leagues_for_dropdown as $league_opt): ?>
                        <?php $selected_league = (isset($form_data_add_match['league_id']) && $form_data_add_match['league_id'] == $league_opt['id']) ? 'selected' : ''; ?>
                        <option value="<?php echo htmlspecialchars($league_opt['id']); ?>" <?php echo $selected_league; ?>>
                            <?php echo htmlspecialchars($league_opt['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div>
            <label for="description_form">Descrição (opcional):</label>
            <textarea id="description_form" name="description" rows="3"><?php echo htmlspecialchars($form_data_add_match['description'] ?? ''); ?></textarea>
        </div>
    </fieldset>

    <fieldset>
        <legend>Capa da Partida (Opcional)</legend>
        <label for="cover_image_file_form">Arquivo da Capa (JPG, PNG, GIF - Máx 2MB):</label>
        <input type="file" id="cover_image_file_form" name="cover_image_file" accept="image/jpeg,image/png,image/gif">
        <img id="cover_image_preview_form" src="#" alt="Prévia da capa" style="display:none; max-height: 100px; margin-top: 10px; border:1px solid #ccc; padding:2px;">
        <?php if (isset($form_data_add_match['cover_image_filename_tmp']) && !empty($form_data_add_match['cover_image_filename_tmp'])): ?>
            <p style="font-size:0.9em; color: #555; margin-top:5px;">Última seleção (se falhou): <?php echo htmlspecialchars($form_data_add_match['cover_image_filename_tmp']); ?></p>
        <?php endif; ?>
         <p style="font-size:0.8em; color:#777; margin-top:5px;">Se nenhuma capa for enviada, a <a href="manage_settings.php#default-cover-management" target="_blank">capa padrão do site</a> (se configurada) será usada.</p>
    </fieldset>

    <fieldset>
        <legend>SEO</legend>
        <div>
            <label for="meta_description_form">Meta Descrição (máx ~160 caracteres):</label>
            <textarea id="meta_description_form" name="meta_description" rows="3"><?php echo htmlspecialchars($form_data_add_match['meta_description'] ?? ''); ?></textarea>
            <span id="meta_description_counter_form" style="display: block; font-size: 0.85em; color: #666; margin-top: 4px;">0/160</span>
        </div>
        <div>
            <label for="meta_keywords_form">Meta Keywords (separadas por vírgula):</label>
            <input type="text" id="meta_keywords_form" name="meta_keywords" value="<?php echo htmlspecialchars($form_data_add_match['meta_keywords'] ?? ''); ?>" placeholder="palavra1, outra palavra, termo chave">
        </div>
    </fieldset>

    <div><button type="submit" class="btn-add-match">➕ Adicionar Jogo</button></div>
    <div id="form_submission_loader_form" style="display: none; margin-top: 10px; text-align: center; font-style: italic;">Salvando...</div>
</form>
<hr>

<script>
// Encapsulate scripts to avoid conflicts if this template is loaded multiple times (though not expected here)
(function() {
    // Script for Cover Image Preview specific to this form
    const coverImageInputForm = document.getElementById('cover_image_file_form');
    const coverImagePreviewForm = document.getElementById('cover_image_preview_form');

    if (coverImageInputForm && coverImagePreviewForm) {
        coverImageInputForm.addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    coverImagePreviewForm.src = e.target.result;
                    coverImagePreviewForm.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                coverImagePreviewForm.src = '#';
                coverImagePreviewForm.style.display = 'none';
            }
        });
    }

    // Script for Meta Description Counter specific to this form
    const metaDescriptionTextareaForm = document.getElementById('meta_description_form');
    const metaDescriptionCounterForm = document.getElementById('meta_description_counter_form');
    const metaDescriptionLimitForm = 160;

    if (metaDescriptionTextareaForm && metaDescriptionCounterForm) {
        metaDescriptionTextareaForm.addEventListener('input', function() {
            const currentLength = this.value.length;
            metaDescriptionCounterForm.textContent = currentLength + '/' + metaDescriptionLimitForm;
            if (currentLength > metaDescriptionLimitForm) {
                metaDescriptionCounterForm.style.color = 'red'; // Or add a class
            } else {
                metaDescriptionCounterForm.style.color = '#666'; // Or remove class
            }
        });
        // Trigger on page load in case there's pre-filled text
        metaDescriptionTextareaForm.dispatchEvent(new Event('input'));
    }

    // Script for Add Match Form Loader specific to this form
    const addMatchFormInTemplate = document.querySelector('form[action="add_match.php"]'); // More specific if multiple forms exist
    if (addMatchFormInTemplate) { // Check if we are in the context of this specific form
        const addMatchSubmitButtonInTemplate = addMatchFormInTemplate.querySelector('button[type="submit"].btn-add-match');
        const addMatchLoaderDivInTemplate = document.getElementById('form_submission_loader_form');

        if (addMatchSubmitButtonInTemplate && addMatchLoaderDivInTemplate) {
            addMatchFormInTemplate.addEventListener('submit', function(event) {
                let formIsValid = true;
                // Basic check for required fields, HTML5 validation handles most of it
                addMatchFormInTemplate.querySelectorAll('[required]').forEach(function(input) {
                    if (!input.value.trim()) {
                        formIsValid = false;
                    }
                });

                if (formIsValid) {
                    addMatchSubmitButtonInTemplate.disabled = true;
                    addMatchLoaderDivInTemplate.style.display = 'block';
                } else {
                    addMatchSubmitButtonInTemplate.disabled = false;
                    addMatchLoaderDivInTemplate.style.display = 'none';
                }
            });
        }
    }

    // Initialize Searchable Selects for this form
    const homeTeamSelectForm = document.getElementById('home_team_id_form');
    if (homeTeamSelectForm && typeof makeSelectSearchable === 'function') {
        makeSelectSearchable(homeTeamSelectForm);
    }

    const awayTeamSelectForm = document.getElementById('away_team_id_form');
    if (awayTeamSelectForm && typeof makeSelectSearchable === 'function') {
        makeSelectSearchable(awayTeamSelectForm);
    }

    const leagueSelectForm = document.getElementById('league_id_form');
    if (leagueSelectForm && typeof makeSelectSearchable === 'function') {
        makeSelectSearchable(leagueSelectForm);
    }
})();
</script>
