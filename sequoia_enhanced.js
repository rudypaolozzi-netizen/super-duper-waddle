/**
 * Fonctionnalités avancées pour le planning Sequoia
 * À ajouter dans index.html juste avant la fermeture </body>
 */

// Variables pour le drag & drop
let draggedTask = null;
let draggedTaskElement = null;

// Initialiser le drag & drop sur les tâches
function initDragDrop() {
    const taskBlocks = document.querySelectorAll('.task-block');
    
    taskBlocks.forEach(block => {
        block.setAttribute('draggable', 'true');
        
        block.addEventListener('dragstart', (e) => {
            draggedTaskElement = e.target;
            const taskId = e.target.dataset.taskId;
            draggedTask = tasks.find(t => t.id == taskId);
            e.target.style.opacity = '0.5';
            e.dataTransfer.effectAllowed = 'move';
        });
        
        block.addEventListener('dragend', (e) => {
            e.target.style.opacity = '1';
            draggedTask = null;
            draggedTaskElement = null;
        });
    });
    
    // Rendre les cellules droppables
    const dayCells = document.querySelectorAll('.day-cell');
    dayCells.forEach(cell => {
        cell.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            cell.style.background = '#e3f2fd';
        });
        
        cell.addEventListener('dragleave', (e) => {
            cell.style.background = '';
        });
        
        cell.addEventListener('drop', async (e) => {
            e.preventDefault();
            cell.style.background = '';
            
            if (!draggedTask) return;
            
            const newUserId = parseInt(cell.dataset.userId);
            const newDate = cell.dataset.date;
            
            // Mettre à jour la tâche
            const formData = new FormData();
            formData.append('action', 'save_task');
            formData.append('task_id', draggedTask.id);
            formData.append('user_id', newUserId);
            formData.append('folder_id', draggedTask.folder_id);
            formData.append('date', newDate);
            formData.append('hours', draggedTask.hours);
            formData.append('comment', draggedTask.comment || '');
            
            await fetch('api.php', { method: 'POST', body: formData });
            await loadTasks();
            renderPlanning();
            initDragDrop();
        });
    });
}

// Étirer une tâche sur plusieurs jours
function stretchTask(taskId, days) {
    const task = tasks.find(t => t.id === taskId);
    if (!task) return;
    
    const confirmed = confirm(`Étirer cette tâche sur ${days} jours ?\n${task.hours}h seront réparties équitablement.`);
    if (!confirmed) return;
    
    const hoursPerDay = (task.hours / days).toFixed(2);
    const startDate = new Date(task.date);
    
    // Créer les nouvelles tâches
    const promises = [];
    for (let i = 1; i < days; i++) {
        const newDate = new Date(startDate);
        newDate.setDate(newDate.getDate() + i);
        
        const formData = new FormData();
        formData.append('action', 'save_task');
        formData.append('user_id', task.user_id);
        formData.append('folder_id', task.folder_id);
        formData.append('date', formatDate(newDate));
        formData.append('hours', hoursPerDay);
        formData.append('comment', task.comment || '');
        
        promises.push(fetch('api.php', { method: 'POST', body: formData }));
    }
    
    // Mettre à jour la tâche originale
    const updateForm = new FormData();
    updateForm.append('action', 'save_task');
    updateForm.append('task_id', taskId);
    updateForm.append('user_id', task.user_id);
    updateForm.append('folder_id', task.folder_id);
    updateForm.append('date', task.date);
    updateForm.append('hours', hoursPerDay);
    updateForm.append('comment', task.comment || '');
    
    promises.push(fetch('api.php', { method: 'POST', body: updateForm }));
    
    Promise.all(promises).then(() => {
        loadTasks().then(() => {
            renderPlanning();
            initDragDrop();
        });
    });
}

// Splitter une tâche en plusieurs jours
async function splitTask(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task) return;
    
    const days = prompt(`Sur combien de jours voulez-vous répartir les ${task.hours}h ?`, '2');
    if (!days || isNaN(days) || days < 2) return;
    
    await stretchTask(taskId, parseInt(days));
}

// Dupliquer une tâche
async function duplicateTask(taskId) {
    const task = tasks.find(t => t.id === taskId);
    if (!task) return;
    
    const formData = new FormData();
    formData.append('action', 'save_task');
    formData.append('user_id', task.user_id);
    formData.append('folder_id', task.folder_id);
    formData.append('date', task.date);
    formData.append('hours', task.hours);
    formData.append('comment', (task.comment || '') + ' [COPIE]');
    
    await fetch('api.php', { method: 'POST', body: formData });
    await loadTasks();
    renderPlanning();
    initDragDrop();
}

// Valider toutes les tâches d'un membre pour une semaine
async function validateWeekForUser(userId) {
    const startDate = formatDate(currentWeekStart);
    const endDate = new Date(currentWeekStart);
    endDate.setDate(endDate.getDate() + 6);
    
    const userTasks = tasks.filter(t => 
        t.user_id === userId && 
        t.date >= startDate && 
        t.date <= formatDate(endDate) &&
        !t.validated
    );
    
    if (userTasks.length === 0) {
        alert('Aucune tâche à valider');
        return;
    }
    
    const user = users.find(u => u.id === userId);
    const confirmed = confirm(`Valider ${userTasks.length} tâche(s) pour ${user.name} ?`);
    if (!confirmed) return;
    
    const promises = userTasks.map(task => {
        const formData = new FormData();
        formData.append('action', 'validate_task');
        formData.append('id', task.id);
        return fetch('api.php', { method: 'POST', body: formData });
    });
    
    await Promise.all(promises);
    await loadTasks();
    renderPlanning();
    initDragDrop();
    alert('Tâches validées avec succès !');
}

// Calculer les statistiques d'un membre
function showUserStats(userId) {
    const startDate = formatDate(currentWeekStart);
    const endDate = new Date(currentWeekStart);
    endDate.setDate(endDate.getDate() + 6);
    
    const userTasks = tasks.filter(t => 
        t.user_id === userId && 
        t.date >= startDate && 
        t.date <= formatDate(endDate)
    );
    
    if (userTasks.length === 0) {
        alert('Aucune donnée pour cette période');
        return;
    }
    
    const user = users.find(u => u.id === userId);
    const totalHours = userTasks.reduce((sum, t) => sum + parseFloat(t.hours), 0);
    const validatedHours = userTasks.filter(t => t.validated).reduce((sum, t) => sum + parseFloat(t.hours), 0);
    const validatedCount = userTasks.filter(t => t.validated).length;
    
    // Grouper par dossier
    const byFolder = {};
    userTasks.forEach(t => {
        if (!byFolder[t.folder_name]) {
            byFolder[t.folder_name] = { hours: 0, color: t.folder_color };
        }
        byFolder[t.folder_name].hours += parseFloat(t.hours);
    });
    
    let message = `📊 Statistiques pour ${user.name}\n`;
    message += `Semaine du ${formatDateFr(currentWeekStart)}\n\n`;
    message += `⏱️ Total : ${totalHours.toFixed(2)}h\n`;
    message += `✅ Validé : ${validatedHours.toFixed(2)}h (${validatedCount} tâches)\n`;
    message += `⏳ Non validé : ${(totalHours - validatedHours).toFixed(2)}h\n\n`;
    message += `📁 Par dossier :\n`;
    
    Object.entries(byFolder).forEach(([folder, data]) => {
        message += `• ${folder} : ${data.hours.toFixed(2)}h\n`;
    });
    
    alert(message);
}

// Recherche rapide de tâches
function searchTasks(query) {
    if (!query) {
        renderPlanning();
        return;
    }
    
    query = query.toLowerCase();
    const filteredTasks = tasks.filter(t => 
        t.folder_name.toLowerCase().includes(query) ||
        t.user_name.toLowerCase().includes(query) ||
        (t.comment && t.comment.toLowerCase().includes(query))
    );
    
    // Réafficher uniquement les tâches filtrées
    console.log(`${filteredTasks.length} résultat(s) trouvé(s)`);
    // TODO: Implémenter le filtrage visuel
}

// Copier la semaine précédente
async function copyPreviousWeek() {
    const confirmed = confirm('Copier toutes les tâches de la semaine précédente vers la semaine actuelle ?');
    if (!confirmed) return;
    
    const prevWeekStart = new Date(currentWeekStart);
    prevWeekStart.setDate(prevWeekStart.getDate() - 7);
    const prevWeekEnd = new Date(prevWeekStart);
    prevWeekEnd.setDate(prevWeekEnd.getDate() + 6);
    
    const response = await fetch(`api.php?action=get_tasks&start_date=${formatDate(prevWeekStart)}&end_date=${formatDate(prevWeekEnd)}`);
    const prevTasks = await response.json();
    
    if (prevTasks.length === 0) {
        alert('Aucune tâche à copier');
        return;
    }
    
    const promises = prevTasks.map(task => {
        const newDate = new Date(task.date);
        newDate.setDate(newDate.getDate() + 7);
        
        const formData = new FormData();
        formData.append('action', 'save_task');
        formData.append('user_id', task.user_id);
        formData.append('folder_id', task.folder_id);
        formData.append('date', formatDate(newDate));
        formData.append('hours', task.hours);
        formData.append('comment', task.comment || '');
        
        return fetch('api.php', { method: 'POST', body: formData });
    });
    
    await Promise.all(promises);
    await loadTasks();
    renderPlanning();
    initDragDrop();
    alert(`${prevTasks.length} tâche(s) copiée(s) !`);
}

// Raccourcis clavier
document.addEventListener('keydown', (e) => {
    // Ctrl + S : Sauvegarder (si modal ouverte)
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        if (document.getElementById('task-modal').classList.contains('active')) {
            document.getElementById('task-form').requestSubmit();
        }
    }
    
    // Échap : Fermer les modales
    if (e.key === 'Escape') {
        closeTaskModal();
        closeFolderModal();
        closeUserModal();
    }
    
    // Flèches gauche/droite : Navigation semaines
    if (e.ctrlKey && e.key === 'ArrowLeft') {
        previousWeek();
    }
    if (e.ctrlKey && e.key === 'ArrowRight') {
        nextWeek();
    }
});

// Menu contextuel sur clic droit
function showContextMenu(e, taskId) {
    e.preventDefault();
    
    const existingMenu = document.getElementById('context-menu');
    if (existingMenu) existingMenu.remove();
    
    const menu = document.createElement('div');
    menu.id = 'context-menu';
    menu.style.cssText = `
        position: fixed;
        top: ${e.clientY}px;
        left: ${e.clientX}px;
        background: white;
        border: 1px solid #015871;
        border-radius: 5px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        min-width: 180px;
    `;
    
    const task = tasks.find(t => t.id == taskId);
    
    menu.innerHTML = `
        <div style="padding: 8px 12px; cursor: pointer; hover: background: #f0f0f0;" onclick="editTask(${taskId})">
            ✏️ Modifier
        </div>
        <div style="padding: 8px 12px; cursor: pointer;" onclick="duplicateTask(${taskId})">
            📋 Dupliquer
        </div>
        <div style="padding: 8px 12px; cursor: pointer;" onclick="splitTask(${taskId})">
            ✂️ Diviser sur plusieurs jours
        </div>
        ${!task.validated ? `
        <div style="padding: 8px 12px; cursor: pointer;" onclick="validateTaskFromMenu(${taskId})">
            ✅ Valider
        </div>
        ` : ''}
        <div style="padding: 8px 12px; cursor: pointer; color: #c53030;" onclick="confirmDeleteTaskFromMenu(${taskId})">
            🗑️ Supprimer
        </div>
    `;
    
    document.body.appendChild(menu);
    
    // Fermer au clic ailleurs
    setTimeout(() => {
        document.addEventListener('click', function closeMenu() {
            menu.remove();
            document.removeEventListener('click', closeMenu);
        });
    }, 100);
}

async function validateTaskFromMenu(taskId) {
    const formData = new FormData();
    formData.append('action', 'validate_task');
    formData.append('id', taskId);
    await fetch('api.php', { method: 'POST', body: formData });
    await loadTasks();
    renderPlanning();
    initDragDrop();
}

async function confirmDeleteTaskFromMenu(taskId) {
    if (!confirm('Supprimer cette tâche ?')) return;
    const formData = new FormData();
    formData.append('action', 'delete_task');
    formData.append('id', taskId);
    await fetch('api.php', { method: 'POST', body: formData });
    await loadTasks();
    renderPlanning();
    initDragDrop();
}

// Initialiser au chargement
window.addEventListener('load', () => {
    // Attendre que le planning soit chargé
    setTimeout(() => {
        initDragDrop();
    }, 1000);
});

console.log('✅ Fonctionnalités avancées chargées');