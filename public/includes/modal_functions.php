<?php

function renderAttendanceModal() {
    ?>
    <div id="attendanceModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('attendanceModal')">&times;</span>
            <h2 id="attendanceModalTitle">Teilnehmerliste</h2>
            <div id="attendanceContent">
                <div class="attendance-group">
                    <h4>👍 Zugesagt</h4>
                    <ul id="list-yes"></ul>
                </div>
                <div class="attendance-group">
                    <h4>❓ Vielleicht</h4>
                    <ul id="list-maybe"></ul>
                </div>
                <div class="attendance-group">
                    <h4>👎 Abgesagt</h4>
                    <ul id="list-no"></ul>
                </div>
                <div class="attendance-group">
                    <h4>⚪ Noch offen</h4>
                    <ul id="list-none"></ul>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function renderAddMatchModal($teams) {
    ?>
    <div id="addMatchModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addMatchModal')">&times;</span>
            <h2>Neues Spiel</h2>
            <form action="action.php" method="POST">
                <input type="hidden" name="action" value="add_match">
                <div class="form-row">
                    <div>
                        <label>Datum:</label>
                        <input type="date" name="match_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Startzeit:</label>
                        <input type="time" name="start_time" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Treffen:</label>
                        <input type="time" name="meeting_time">
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Gegner:</label>
                        <input type="text" name="opponent" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Heimspiel:</label>
                        <input type="checkbox" name="is_home_game" id="add_match_is_home">
                    </div>
                </div>
                <div class="form-row">
                    <div id="add_location_container">
                        <label>Anschrift:</label>
                        <input type="text" name="location" placeholder="Straße, PLZ Ort">
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Mannschaft:</label>
                        <select name="team_id" required>
                            <option value="">-- Mannschaft wählen --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-confirm-ok">Anlegen</button>
            </form>
        </div>
    </div>
    <?php
}

function renderEditMatchModal($teams) {
    ?>
    <div id="editMatchModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editMatchModal')">&times;</span>
            <h2>Spiel bearbeiten</h2>
            <form action="action.php" method="POST" id="editMatchForm">
                <input type="hidden" name="action" value="edit_match">
                <input type="hidden" name="match_id" id="edit_match_id">
                <div class="form-row">
                    <div>
                        <label>Datum:</label>
                        <input type="date" name="match_date" id="edit_match_date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Startzeit:</label>
                        <input type="time" name="start_time" id="edit_match_start_time" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Treffen:</label>
                        <input type="time" name="meeting_time" id="edit_match_meeting_time">
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Gegner:</label>
                        <input type="text" name="opponent" id="edit_match_opponent" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Heimspiel:</label>
                        <input type="checkbox" name="is_home_game" id="edit_match_is_home">
                    </div>
                </div>
                <div class="form-row">
                    <div id="edit_location_container">
                        <label>Anschrift (für Auswärtsspiele):</label>
                        <input type="text" name="location" id="edit_match_location" placeholder="Straße, PLZ Ort">
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Mannschaft:</label>
                        <select name="team_id" id="edit_match_team_id" required>
                            <option value="">-- Mannschaft wählen --</option>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-confirm-ok">Speichern</button>
            </form>
        </div>
    </div>
    <?php
}

function renderAddTrainingModal($teams) {
    ?>
    <div id="addTrainingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addTrainingModal')">&times;</span>
            <h2>Neues Training</h2>
            <form action="action.php" method="POST">
                <input type="hidden" name="action" value="add_training">
                
                <div class="modal-section">
                    <label class="training-type-label">
                        <input type="radio" name="training_type" value="single" checked onclick="toggleTrainingType('single')"> Einmalig
                    </label>
                    <label class="training-type-label">
                        <input type="radio" name="training_type" value="weekly" onclick="toggleTrainingType('weekly')"> Wöchentlich
                    </label>
                </div>
                
                <div id="single_training_fields">
                    <div class="form-row">
                        <div>
                            <label>Datum:</label>
                            <input type="date" name="training_date" required>
                        </div>
                    </div>
                </div>

                <div id="weekly_training_fields" class="weekly-fields">
                    <div class="form-row">
                        <div>
                            <label>Wochentag:</label>
                            <select name="day_of_week">
                                <option value="1">Montag</option>
                                <option value="2">Dienstag</option>
                                <option value="3">Mittwoch</option>
                                <option value="4">Donnerstag</option>
                                <option value="5">Freitag</option>
                                <option value="6">Samstag</option>
                                <option value="0">Sonntag</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label>Startdatum:</label>
                            <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>Uhrzeit:</label>
                        <input type="time" name="training_time" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Mannschaften:</label>
                        <select name="team_ids[]" multiple required>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-confirm-ok">Anlegen</button>
            </form>
        </div>
    </div>
    <?php
}

function renderEditTrainingModal($teams) {
    ?>
    <div id="editTrainingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editTrainingModal')">&times;</span>
            <h2>Training bearbeiten</h2>
            <form action="action.php" method="POST" id="editTrainingForm">
                <input type="hidden" name="action" value="edit_training">
                <input type="hidden" name="training_id" id="edit_training_id">
                <input type="hidden" name="edit_mode" id="edit_training_mode" value="single">
                <input type="hidden" name="occurrence_date" id="edit_training_occurrence_date" value="">

                <div id="edit_training_series_choice" class="modal-section" style="display:none;">
                    <label class="training-type-label">
                        <input type="radio" name="edit_scope" value="single_occurrence" checked onclick="toggleEditTrainingScope('single_occurrence')"> Nur diesen Termin
                    </label>
                    <label class="training-type-label">
                        <input type="radio" name="edit_scope" value="series" onclick="toggleEditTrainingScope('series')"> Gesamte Serie
                    </label>
                </div>

                <div id="edit_training_single_fields">
                    <div class="form-row">
                        <div>
                            <label>Datum:</label>
                            <input type="date" name="training_date" id="edit_training_date" required>
                        </div>
                    </div>
                </div>

                <div id="edit_training_series_fields" style="display:none;">
                    <div class="form-row">
                        <div>
                            <label>Wochentag:</label>
                            <select name="day_of_week" id="edit_training_day_of_week">
                                <option value="1">Montag</option>
                                <option value="2">Dienstag</option>
                                <option value="3">Mittwoch</option>
                                <option value="4">Donnerstag</option>
                                <option value="5">Freitag</option>
                                <option value="6">Samstag</option>
                                <option value="0">Sonntag</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label>Uhrzeit:</label>
                        <input type="time" name="training_time" id="edit_training_time" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Mannschaften:</label>
                        <select name="team_ids[]" id="edit_training_team_ids" multiple required>
                            <?php foreach ($teams as $team): ?>
                                <option value="<?php echo $team['id']; ?>"><?php echo htmlspecialchars($team['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-confirm-ok">Speichern</button>
            </form>
        </div>
    </div>
    <?php
}

function renderAddPlayerModal($teams, $player_id) {
    $all_other_players = getAllPlayers();
    ?>
    <div id="addPlayerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addPlayerModal')">&times;</span>
            <h2>Neuer Spieler</h2>
            <form action="action.php" method="POST">
                <input type="hidden" name="action" value="add_player">
                <div class="form-row">
                    <div>
                        <label>Name:</label>
                        <input type="text" name="name" required>
                    </div>
                </div>
                <?php if (isClubAdmin() || isAnyTeamAdmin($player_id)): ?>
                    <div class="form-row">
                        <div>
                            <label>Vereinsadmin:</label>
                            <input type="checkbox" name="is_club_admin" <?php echo !isClubAdmin() ? 'disabled' : ''; ?>>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="form-row">
                    <div>
                        <label>Mannschaften:</label>
                        <div class="team-roles-list">
                            <?php
                            $selectable_teams = isClubAdmin() ? $teams : getAdminTeams($player_id);
                            foreach ($selectable_teams as $team): ?>
                                <div class="team-role-item">
                                    <span class="team-role-name"><?php echo htmlspecialchars($team['name']); ?></span>
                                    <label><input type="checkbox" name="team_training[<?php echo $team['id']; ?>]" value="1"> Training</label>
                                    <label><input type="checkbox" name="team_admin[<?php echo $team['id']; ?>]" value="1"> Admin</label>
                                    <label><input type="checkbox" name="team_player[<?php echo $team['id']; ?>]" value="1"> Spieler</label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Darf abstimmen für:</label>
                        <select name="voter_permission_player_ids[]" id="add_player_voter_permissions" multiple>
                            <?php 
                            foreach ($all_other_players as $other_player): ?>
                                <option value="<?php echo $other_player['id']; ?>"><?php echo htmlspecialchars($other_player['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-confirm-ok">Anlegen</button>
            </form>
        </div>
    </div>
    <?php
}

function renderEditPlayerModal($teams, $player_id) {
    $all_other_players = getAllPlayers();
    ?>
    <div id="editPlayerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editPlayerModal')">&times;</span>
            <h2>Spieler bearbeiten</h2>
            <form action="action.php" method="POST">
                <input type="hidden" name="action" value="edit_player">
                <input type="hidden" name="player_id" id="edit_player_id">
                <div class="form-row">
                    <div>
                        <label>Name:</label>
                        <input type="text" name="name" id="edit_player_name" required>
                    </div>
                </div>
                <?php if (isClubAdmin()): ?>
                <div class="form-row">
                    <div>
                        <label>Vereinsadmin:</label>
                        <input type="checkbox" name="is_club_admin" id="edit_player_is_club_admin">
                    </div>
                </div>
                <?php endif; ?>
                <div class="form-row">
                    <div>
                        <label>Mannschaften:</label>
                        <div class="team-roles-list" id="edit_player_team_roles">
                            <?php
                            $selectable_teams = isClubAdmin() ? $teams : getAdminTeams($player_id);
                            foreach ($selectable_teams as $team): ?>
                                <div class="team-role-item" data-team-id="<?php echo $team['id']; ?>">
                                    <span class="team-role-name"><?php echo htmlspecialchars($team['name']); ?></span>
                                    <label><input type="checkbox" name="team_training[<?php echo $team['id']; ?>]" value="1" class="team-training-cb"> Training</label>
                                    <label><input type="checkbox" name="team_admin[<?php echo $team['id']; ?>]" value="1" class="team-admin-cb"> Admin</label>
                                    <label><input type="checkbox" name="team_player[<?php echo $team['id']; ?>]" value="1" class="team-player-cb"> Spieler</label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Darf abstimmen für:</label>
                        <select name="voter_permission_player_ids[]" id="edit_player_voter_permissions" multiple>
                            <?php 
                            foreach ($all_other_players as $other_player): ?>
                                <option value="<?php echo $other_player['id']; ?>"><?php echo htmlspecialchars($other_player['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-confirm-ok">Speichern</button>
            </form>
        </div>
    </div>
    <?php
}

function renderAddTeamModal() {
    ?>
    <div id="addTeamModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addTeamModal')">&times;</span>
            <h2>Neue Mannschaft</h2>
            <form action="action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_team">
                <div class="form-row">
                    <div>
                        <label>Name:</label>
                        <input type="text" name="name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Logo:</label>
                        <input type="file" name="logo" accept="image/*">
                    </div>
                </div>
                <button type="submit" class="btn-confirm-ok">Anlegen</button>
            </form>
        </div>
    </div>
    <?php
}

function renderConfirmModal() {
    ?>
    <div id="confirmModal" class="modal">
        <div class="modal-content confirm-modal-content">
            <h2 id="confirmModalTitle">Bestätigung</h2>
            <p id="confirmModalMessage"></p>
            <div class="confirm-modal-buttons" id="confirmModalButtons">
                <button type="button" class="btn-confirm-cancel" onclick="closeConfirmModal()">Abbrechen</button>
                <button type="button" class="btn-confirm-ok" id="confirmModalOk">Löschen</button>
            </div>
            <div class="confirm-modal-buttons" id="confirmModalSeriesButtons" style="display:none;">
                <button type="button" class="btn-confirm-cancel" onclick="closeConfirmModal()">Abbrechen</button>
                <button type="button" class="btn-confirm-ok" id="confirmModalDeleteSingle">Nur diesen Termin</button>
                <button type="button" class="btn-confirm-ok" id="confirmModalDeleteSeries">Gesamte Serie</button>
            </div>
        </div>
    </div>
    <?php
}

function renderEditTeamModal() {
    ?>
    <div id="editTeamModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editTeamModal')">&times;</span>
            <h2>Mannschaft bearbeiten</h2>
            <form action="action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_team">
                <input type="hidden" name="team_id" id="edit_team_id">
                <div class="form-row">
                    <div>
                        <label>Name:</label>
                        <input type="text" name="name" id="edit_team_name" required>
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label>Logo (optional):</label>
                        <input type="file" name="logo" accept="image/*">
                    </div>
                </div>
                <button type="submit" class="btn-confirm-ok">Speichern</button>
            </form>
        </div>
    </div>
    <?php
}
