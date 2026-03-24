    </main>
    
    <?php 
    require_once __DIR__ . '/modal_functions.php';
    
    renderAttendanceModal();
    renderConfirmModal();

    $isClubAdmin = isClubAdmin();
    if ($isClubAdmin || isAnyTeamAdmin($player_id ?? 0)) {
        renderAddPlayerModal($teams ?? [], $player_id ?? 0);
        renderEditPlayerModal($teams ?? [], $player_id ?? 0);
        renderAddMatchModal($teams ?? []);
        renderAddTrainingModal($teams ?? []);
        renderEditMatchModal($teams ?? []);
        renderEditTrainingModal($teams ?? []);
    }
    
    if ($isClubAdmin) {
        renderAddTeamModal();
        renderEditTeamModal();
    }
    ?>

    <div id="voteTargetMenu" class="vote-target-menu" aria-hidden="true"></div>

    <div id="qrCodeModal" class="modal">
        <div class="modal-content" style="text-align:center;">
            <span class="close" onclick="closeModal('qrCodeModal')">&times;</span>
            <h2 id="qrCodeTitle">QR-Code</h2>
            <div id="qrCodeContainer" style="display:flex;justify-content:center;"></div>
        </div>
    </div>

    <script src="js/qrcode.min.js"></script>
    <script>
        const csrfToken = <?php echo json_encode(generateCsrfToken()); ?>;
        const playerHash = localStorage.getItem('playerHash') || '';
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'block';
                history.pushState({ modalId: modalId }, "");
            }
        }

        function closeModal(modalId, fromHistory = false) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                if (!fromHistory && history.state && history.state.modalId === modalId) {
                    history.back();
                }
            }
        }

        window.onpopstate = function(event) {
            // Alle Modale schließen
            document.querySelectorAll('.modal').forEach(modal => {
                closeModal(modal.id, true);
            });
        };

        // voteContext: { voteTargetIds: [id,...], eventType, eventId, occurrenceDate, defaultPlayerId } oder null
        let _currentAttendanceVoteContext = null;

        function showAttendance(attendance, title, voteContext) {
            _currentAttendanceVoteContext = voteContext || null;
            document.getElementById('attendanceModalTitle').innerText = title;
            const lists = {
                yes: document.getElementById('list-yes'),
                maybe: document.getElementById('list-maybe'),
                no: document.getElementById('list-no'),
                none: document.getElementById('list-none')
            };
            Object.values(lists).forEach(list => list.innerHTML = '');
            const voteTargetIds = voteContext ? voteContext.voteTargetIds : [];
            const defaultPlayerId = voteContext ? String(voteContext.defaultPlayerId) : null;
            attendance.forEach(entry => {
                const li = document.createElement('li');
                li.dataset.playerId = entry.player_id;
                const nameSpan = document.createElement('span');
                nameSpan.textContent = entry.name;
                li.appendChild(nameSpan);
                // Mini-Vote-Buttons für andere Spieler, für die man abstimmen darf
                if (voteTargetIds.includes(entry.player_id) || String(entry.player_id) === defaultPlayerId) {
                    const btnGroup = document.createElement('span');
                    btnGroup.className = 'attendance-vote-btns';
                    [{status: 'yes', icon: 'fa-thumbs-up', title: 'Zusage'}, {status: 'maybe', icon: 'fa-question', title: 'Vielleicht'}, {status: 'no', icon: 'fa-thumbs-down', title: 'Absage'}].forEach(v => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'attendance-vote-btn attendance-vote-' + v.status;
                        btn.title = v.title;
                        btn.innerHTML = '<i class="fa-solid ' + v.icon + '"></i>';
                        btn.addEventListener('click', () => attendanceVote(entry.player_id, v.status, li));
                        btnGroup.appendChild(btn);
                    });
                    li.appendChild(btnGroup);
                }
                if (lists[entry.status]) lists[entry.status].appendChild(li);
            });
            Object.keys(lists).forEach(status => {
                if (lists[status].children.length === 0) {
                    const li = document.createElement('li');
                    li.textContent = 'Keine Einträge';
                    li.classList.add('empty-list');
                    lists[status].appendChild(li);
                }
            });
            openModal('attendanceModal');
        }

        async function attendanceVote(playerId, status, liElement) {
            const ctx = _currentAttendanceVoteContext;
            if (!ctx) return;
            const formData = new FormData();
            formData.append('action', 'vote');
            formData.append('event_type', ctx.eventType);
            formData.append('event_id', ctx.eventId);
            formData.append('target_player_id', playerId);
            formData.append('status', status);
            formData.append('csrf_token', csrfToken);
            formData.append('hash', playerHash);
            if (ctx.occurrenceDate) formData.append('occurrence_date', ctx.occurrenceDate);
            try {
                const response = await fetch('action.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.attendance) {
                        // Modal mit neuen Daten aktualisieren
                        const title = document.getElementById('attendanceModalTitle').innerText;
                        showAttendance(data.attendance, title, ctx);
                        // Auch den Attendance-Button auf der Karte aktualisieren
                        updateCardAttendanceButton(ctx, data);
                    }
                }
            } catch (error) {
                console.error('Error voting from attendance:', error);
            }
        }

        function updateCardAttendanceButton(ctx, data) {
            // Finde die vote-form auf der Karte und aktualisiere counts + attendance-btn
            const forms = document.querySelectorAll('.vote-form');
            forms.forEach(form => {
                const eventIdInput = form.querySelector('input[name="event_id"]');
                const eventTypeInput = form.querySelector('input[name="event_type"]');
                const occurrenceDateInput = form.querySelector('input[name="occurrence_date"]');
                if (!eventIdInput || !eventTypeInput) return;
                const formOccurrence = occurrenceDateInput ? occurrenceDateInput.value : '';
                const ctxOccurrence = ctx.occurrenceDate || '';
                if (eventIdInput.value === String(ctx.eventId) && eventTypeInput.value === ctx.eventType && formOccurrence === ctxOccurrence) {
                    // Counts aktualisieren
                    if (data.counts) {
                        form.querySelectorAll('button[type="submit"]').forEach(btn => {
                            if (data.counts[btn.value] !== undefined) {
                                const countSpan = btn.querySelector('.count');
                                if (countSpan) countSpan.textContent = data.counts[btn.value];
                            }
                        });
                    }
                    // Attendance-Button aktualisieren
                    if (data.attendance) {
                        const voteButtonsDiv = form.closest('.vote-buttons');
                        const attendanceBtn = voteButtonsDiv ? voteButtonsDiv.querySelector('.btn-attendance') : null;
                        if (attendanceBtn) {
                            const originalOnclick = attendanceBtn.getAttribute('onclick');
                            if (originalOnclick) {
                                const titleMatch = originalOnclick.match(/,\s*"([^"]+)"/);
                                const title = titleMatch ? titleMatch[1] : 'Teilnehmerliste';
                                attendanceBtn.setAttribute('onclick', `showAttendance(${JSON.stringify(data.attendance)}, "${title}", ${JSON.stringify(ctx)})`);
                            }
                        }
                    }
                }
            });
        }

        async function performVote(form, status) {
            const formData = new FormData(form);
            formData.append('status', status);
            if (!formData.has('csrf_token')) formData.append('csrf_token', csrfToken);
            if (!formData.has('hash')) formData.append('hash', playerHash);
            try {
                const response = await fetch('action.php', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        console.info('Vote successful: ', JSON.stringify(data));
                        const targetInput = form.querySelector('input[name="target_player_id"]');
                        const defaultPlayerId = form.dataset.defaultPlayerId;
                        const votedForSelf = !targetInput || !defaultPlayerId || targetInput.value === defaultPlayerId;
                        const buttons = form.querySelectorAll('button[type="submit"]');
                        buttons.forEach(btn => {
                            if (votedForSelf) {
                                if (btn.value === status) btn.classList.add('active');
                                else btn.classList.remove('active');
                            }
                            if (data.counts[btn.value] !== undefined) {
                                const countSpan = btn.querySelector('.count');
                                if (countSpan) countSpan.textContent = data.counts[btn.value];
                            }
                        });
                        // Reset target_player_id back to self after voting for someone else
                        if (targetInput && defaultPlayerId) {
                            targetInput.value = defaultPlayerId;
                        }
                        const voteButtonsDiv = form.closest('.vote-buttons');
                        const attendanceBtn = voteButtonsDiv ? voteButtonsDiv.querySelector('.btn-attendance') : null;
                        if (attendanceBtn) {
                            const originalOnclick = attendanceBtn.getAttribute('onclick');
                            if (originalOnclick) {
                                const titleMatch = originalOnclick.match(/,\s*"([^"]+)"/);
                                const title = titleMatch ? titleMatch[1] : 'Teilnehmerliste';
                                // Preserve voteContext if present
                                const ctxMatch = originalOnclick.match(/,\s*"[^"]+"\s*,\s*(\{.*\})\s*\)/);
                                const ctxArg = ctxMatch ? ', ' + ctxMatch[1] : '';
                                attendanceBtn.setAttribute('onclick', `showAttendance(${JSON.stringify(data.attendance)}, "${title}"${ctxArg})`);
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Error voting:' , error);
                // Im Fehlerfall laden wir die Seite neu als Fallback, falls das Problem persistent ist
                // Aber der User will eigentlich KEINE Weiterleitung.
                // form.submit(); // Wir entfernen das automatische Absenden im Fehlerfall um die Weiterleitung zu vermeiden
            }
        }

        async function handleVote(event) {
            event.preventDefault();
            const form = event.currentTarget;

            // Zuverlässigere Methode um den Status zu bekommen, falls event.submitter nicht existiert
            let status = event.submitter ? event.submitter.value : null;

            // Falls handleVote anders aufgerufen wurde (nicht über submitter), suchen wir den aktiven Button
            if (!status) {
                // Das ist ein Fallback, falls der Browser event.submitter nicht unterstützt
                return; // Ohne Status können wir nichts tun
            }

            await performVote(form, status);
        }

        function getVoteTargets(form) {
            if (!form || !form.dataset.voteTargets) return [];
            try {
                const parsed = JSON.parse(form.dataset.voteTargets);
                return Array.isArray(parsed) ? parsed : [];
            } catch (e) {
                return [];
            }
        }

        function hideVoteTargetMenu() {
            const menu = document.getElementById('voteTargetMenu');
            if (!menu) return;
            menu.classList.remove('open');
            menu.setAttribute('aria-hidden', 'true');
            menu.innerHTML = '';
        }

        function showVoteTargetMenu(button, form, status) {
            const targets = getVoteTargets(form);
            if (targets.length <= 1) return;

            const menu = document.getElementById('voteTargetMenu');
            if (!menu) return;

            menu.innerHTML = '';
            const title = document.createElement('div');
            title.className = 'vote-target-menu-title';
            title.textContent = 'Für wen abstimmen?';
            menu.appendChild(title);

            const list = document.createElement('ul');
            list.className = 'vote-target-menu-list';
            targets.forEach(target => {
                const item = document.createElement('li');
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'vote-target-menu-item';
                btn.textContent = target.name;
                btn.addEventListener('click', () => {
                    const targetInput = form.querySelector('input[name="target_player_id"]');
                    if (targetInput) targetInput.value = target.id;
                    hideVoteTargetMenu();
                    performVote(form, status);
                });
                item.appendChild(btn);
                list.appendChild(item);
            });
            menu.appendChild(list);

            menu.style.left = '50%';
            menu.style.top = '50%';
            menu.classList.add('open');
            menu.setAttribute('aria-hidden', 'false');
        }

        function toggleLocation(checkboxId, containerId) {
            const checkbox = document.getElementById(checkboxId);
            const container = document.getElementById(containerId);
            if (checkbox && container) {
                container.style.display = checkbox.checked ? 'none' : 'grid';
            }
        }

        function filterEvents(teamClass, btn) {
            // Filter Buttons Status aktualisieren
            const buttons = document.querySelectorAll('.filter-btn');
            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            // Event-Cards filtern
            const cards = document.querySelectorAll('.event-card');
            cards.forEach(card => {
                if (teamClass === 'all') {
                    card.style.display = 'block';
                } else {
                    if (card.classList.contains(teamClass)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addHomeCheckbox = document.getElementById('add_match_is_home');
            if (addHomeCheckbox) {
                addHomeCheckbox.addEventListener('change', function() {
                    toggleLocation('add_match_is_home', 'add_location_container');
                });
                toggleLocation('add_match_is_home', 'add_location_container');
            }
            const editHomeCheckbox = document.getElementById('edit_match_is_home');
            if (editHomeCheckbox) {
                editHomeCheckbox.addEventListener('change', function() {
                    toggleLocation('edit_match_is_home', 'edit_location_container');
                });
            }

            const voteForms = document.querySelectorAll('.vote-form');
            voteForms.forEach(form => {
                const buttons = form.querySelectorAll('button[type="submit"]');
                buttons.forEach(button => {
                    button.addEventListener('contextmenu', function(event) {
                        event.preventDefault();
                        button.dataset.suppressClick = '1';
                        showVoteTargetMenu(button, form, button.value);
                    });

                    let pressTimer = null;
                    button.addEventListener('touchstart', function() {
                        pressTimer = setTimeout(() => {
                            button.dataset.suppressClick = '1';
                            showVoteTargetMenu(button, form, button.value);
                        }, 500);
                    }, { passive: true });

                    button.addEventListener('touchend', function() {
                        if (pressTimer) clearTimeout(pressTimer);
                    });

                    button.addEventListener('touchmove', function() {
                        if (pressTimer) clearTimeout(pressTimer);
                    });

                    button.addEventListener('click', function(event) {
                        if (button.dataset.suppressClick === '1') {
                            event.preventDefault();
                            event.stopPropagation();
                            button.dataset.suppressClick = '';
                        }
                    });
                });
            });

            document.addEventListener('click', function(event) {
                const menu = document.getElementById('voteTargetMenu');
                if (!menu || !menu.classList.contains('open')) return;
                if (!menu.contains(event.target)) {
                    hideVoteTargetMenu();
                }
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    hideVoteTargetMenu();
                }
            });

            window.addEventListener('scroll', hideVoteTargetMenu, true);
            window.addEventListener('resize', hideVoteTargetMenu);
        });

        function editMatch(match) {
            document.getElementById('edit_match_id').value = match.id;
            document.getElementById('edit_match_date').value = match.match_date;
            document.getElementById('edit_match_start_time').value = match.start_time;
            document.getElementById('edit_match_meeting_time').value = match.meeting_time;
            document.getElementById('edit_match_opponent').value = match.opponent;
            document.getElementById('edit_match_is_home').checked = match.is_home_game === 1;
            document.getElementById('edit_match_location').value = match.location;
            document.getElementById('edit_match_team_id').value = match.team_id;
            toggleLocation('edit_match_is_home', 'edit_location_container');
            openModal('editMatchModal');
        }

        function editTraining(training) {
            document.getElementById('edit_training_id').value = training.id;
            document.getElementById('edit_training_date').value = training.training_date;
            document.getElementById('edit_training_time').value = training.training_time;
            const teamSelect = document.getElementById('edit_training_team_ids');
            Array.from(teamSelect.options).forEach(option => {
                option.selected = training.teams && training.teams.includes(parseInt(option.value));
            });

            const seriesChoice = document.getElementById('edit_training_series_choice');
            const singleFields = document.getElementById('edit_training_single_fields');
            const seriesFields = document.getElementById('edit_training_series_fields');
            const modeInput = document.getElementById('edit_training_mode');
            const occurrenceDateInput = document.getElementById('edit_training_occurrence_date');
            const dateInput = document.getElementById('edit_training_date');

            if (training.is_weekly == 1) {
                seriesChoice.style.display = 'block';
                occurrenceDateInput.value = training.occurrence_date || training.training_date;
                // Default: Nur diesen Termin
                const radioSingle = document.querySelector('input[name="edit_scope"][value="single_occurrence"]');
                if (radioSingle) radioSingle.checked = true;
                toggleEditTrainingScope('single_occurrence');
                // Wochentag setzen
                document.getElementById('edit_training_day_of_week').value = training.day_of_week;
            } else {
                seriesChoice.style.display = 'none';
                singleFields.style.display = 'block';
                seriesFields.style.display = 'none';
                modeInput.value = 'single';
                occurrenceDateInput.value = '';
                dateInput.required = true;
            }

            openModal('editTrainingModal');
        }

        function toggleEditTrainingScope(scope) {
            const singleFields = document.getElementById('edit_training_single_fields');
            const seriesFields = document.getElementById('edit_training_series_fields');
            const modeInput = document.getElementById('edit_training_mode');
            const dateInput = document.getElementById('edit_training_date');

            if (scope === 'series') {
                singleFields.style.display = 'none';
                seriesFields.style.display = 'block';
                modeInput.value = 'series';
                dateInput.required = false;
            } else {
                singleFields.style.display = 'block';
                seriesFields.style.display = 'none';
                modeInput.value = 'single_occurrence';
                dateInput.required = true;
            }
        }

        function editTeam(team) {
            document.getElementById('edit_team_id').value = team.id;
            document.getElementById('edit_team_name').value = team.name;
            openModal('editTeamModal');
        }

        function editPlayer(player) {
            document.getElementById('edit_player_id').value = player.id;
            document.getElementById('edit_player_name').value = player.name;
            const isClubAdminCheckbox = document.getElementById('edit_player_is_club_admin');
            if (isClubAdminCheckbox) isClubAdminCheckbox.checked = player.is_club_admin == 1;
            const rolesContainer = document.getElementById('edit_player_team_roles');
            if (rolesContainer) {
                rolesContainer.querySelectorAll('.team-role-item').forEach(item => {
                    const teamId = parseInt(item.dataset.teamId);
                    const isInTeam = player.team_ids && player.team_ids.includes(teamId);
                    const isAdmin = player.admin_team_ids && player.admin_team_ids.includes(teamId);
                    const isMatchPlayer = player.match_player_team_ids && player.match_player_team_ids.includes(teamId);
                    const isMatchViewer = player.match_viewer_team_ids && player.match_viewer_team_ids.includes(teamId);
                    item.querySelector('.team-training-cb').checked = isInTeam;
                    item.querySelector('.team-admin-cb').checked = isAdmin;
                    item.querySelector('.team-player-cb').checked = isMatchPlayer;
                    item.querySelector('.team-viewer-cb').checked = isMatchViewer;
                });
            }
            const voterPermSelect = document.getElementById('edit_player_voter_permissions');
            if (voterPermSelect) {
                Array.from(voterPermSelect.options).forEach(option => {
                    option.selected = player.voter_permission_player_ids && player.voter_permission_player_ids.includes(parseInt(option.value));
                });
            }
            openModal('editPlayerModal');
        }

        function toggleTrainingType(type) {
            const singleFields = document.getElementById('single_training_fields');
            const weeklyFields = document.getElementById('weekly_training_fields');
            const trainingDateInput = singleFields.querySelector('input[name="training_date"]');
            if (type === 'single') {
                singleFields.style.display = 'block';
                weeklyFields.style.display = 'none';
                trainingDateInput.required = true;
            } else {
                singleFields.style.display = 'none';
                weeklyFields.style.display = 'block';
                trainingDateInput.required = false;
            }
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                closeModal(event.target.id);
            }
        }

        function confirmDelete(event, message) {
            event.preventDefault();
            const form = event.target;
            document.getElementById('confirmModalMessage').textContent = message;
            document.getElementById('confirmModalButtons').style.display = '';
            document.getElementById('confirmModalSeriesButtons').style.display = 'none';
            document.getElementById('confirmModal').style.display = 'block';
            document.getElementById('confirmModalOk').onclick = function() {
                closeConfirmModal();
                form.removeAttribute('onsubmit');
                form.submit();
            };
        }

        function confirmDeleteTraining(event, isWeekly) {
            event.preventDefault();
            const form = event.target;
            if (!isWeekly) {
                document.getElementById('confirmModalMessage').textContent = 'Soll dieses Training wirklich gelöscht werden?';
                document.getElementById('confirmModalButtons').style.display = '';
                document.getElementById('confirmModalSeriesButtons').style.display = 'none';
                document.getElementById('confirmModal').style.display = 'block';
                document.getElementById('confirmModalOk').onclick = function() {
                    closeConfirmModal();
                    form.removeAttribute('onsubmit');
                    form.submit();
                };
            } else {
                document.getElementById('confirmModalMessage').textContent = 'Soll nur dieser Termin oder die gesamte Trainingsserie gelöscht werden?';
                document.getElementById('confirmModalButtons').style.display = 'none';
                document.getElementById('confirmModalSeriesButtons').style.display = '';
                document.getElementById('confirmModal').style.display = 'block';
                document.getElementById('confirmModalDeleteSingle').onclick = function() {
                    closeConfirmModal();
                    form.querySelector('input[name="delete_mode"]').value = 'single_occurrence';
                    form.removeAttribute('onsubmit');
                    form.submit();
                };
                document.getElementById('confirmModalDeleteSeries').onclick = function() {
                    closeConfirmModal();
                    form.querySelector('input[name="delete_mode"]').value = 'series';
                    form.removeAttribute('onsubmit');
                    form.submit();
                };
            }
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }

        function showQrCode(elementId, title) {
            const input = document.getElementById(elementId);
            if (!input) return;
            const container = document.getElementById('qrCodeContainer');
            container.innerHTML = '';
            new QRCode(container, {
                text: input.value,
                width: 200,
                height: 200,
                correctLevel: QRCode.CorrectLevel.H
            });
            document.getElementById('qrCodeTitle').innerText = title || 'QR-Code';
            openModal('qrCodeModal');
        }

        function copyToClipboard(elementId) {
            const copyText = document.getElementById(elementId);
            copyText.select();
            copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(() => {
                const btn = copyText.nextElementSibling;
                const originalTitle = btn.title;
                btn.innerHTML = '<i class="fa-solid fa-check"></i>';
                btn.title = 'Kopiert!';
                setTimeout(() => {
                    btn.innerHTML = '<i class="fa-regular fa-clipboard"></i>';
                    btn.title = originalTitle;
                }, 2000);
            });
        }

        function copyLoginLink(hash) {
            const url = window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '/') + 'login.php?hash=' + hash;
            navigator.clipboard.writeText(url).then(() => {
            const btn = document.querySelector('.copy-link-btn');
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check"></i>';
            btn.style.backgroundColor = '#2ecc71';
            btn.style.borderColor = '#2ecc71';
            setTimeout(() => {
            btn.innerHTML = originalContent;
            btn.style.backgroundColor = '';
            btn.style.borderColor = '';
        }, 2000);
        }).catch(err => {
            console.error('Fehler beim Kopieren:', err);
            alert('Fehler beim Kopieren des Links.');
        });
        }
        // In Spieler-Modals: "Spiele abstimmen" aktiviert automatisch "Spiele anzeigen"
        document.querySelectorAll('input[name^="team_player["]').forEach(function(cb) {
            cb.addEventListener('change', function() {
                if (this.checked) {
                    const row = this.closest('.team-role-item');
                    if (row) {
                        const viewerCb = row.querySelector('input[name^="team_viewer["]');
                        if (viewerCb && !viewerCb.checked) {
                            viewerCb.checked = true;
                        }
                    }
                }
            });
        });

        document.querySelectorAll('.role-checkbox').forEach(function(cb) {
            cb.addEventListener('change', function() {
                // Wenn "Spiele abstimmen" aktiviert wird, auch "Spiele anzeigen" aktivieren
                if (this.dataset.role === 'isMatchPlayer' && this.checked) {
                    const row = this.closest('.team-player-roles');
                    if (row) {
                        const viewerCb = row.querySelector('[data-role="isMatchViewer"]');
                        if (viewerCb && !viewerCb.checked) {
                            viewerCb.checked = true;
                            viewerCb.dispatchEvent(new Event('change'));
                        }
                    }
                }
                const data = new URLSearchParams();
                data.append('action', 'update_team_player_role');
                data.append('team_id', this.dataset.team);
                data.append('player_id', this.dataset.player);
                data.append('role', this.dataset.role);
                data.append('value', this.checked ? '1' : '0');
                data.append('csrf_token', csrfToken);
                data.append('hash', playerHash);
                fetch('action.php', {
                    method: 'POST',
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                    body: data
                });
            });
        });
    </script>
    <footer style="text-align:center;padding:1rem;color:#aaa;font-size:0.75rem;">
        <a href="help/" style="color:#aaa;text-decoration:none;" title="Hilfe"><i class="fa-solid fa-circle-question"></i> Hilfe</a>
        &middot; Version 1.0.7
    </footer>
</body>
</html>
