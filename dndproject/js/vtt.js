document.addEventListener('DOMContentLoaded', () => {
    const map = document.getElementById('game-map');
    const tokens = document.querySelectorAll('.token');
    
    // Drag & Drop
    let draggedToken = null;
    let initialX = 0;
    let initialY = 0;

    tokens.forEach(token => {
        if (token.getAttribute('draggable') === 'true') {
            token.addEventListener('dragstart', (e) => {
                draggedToken = token;
                // Just needed for Firefox sometimes
                e.dataTransfer.effectAllowed = 'move'; 
                e.dataTransfer.setData('text/plain', token.id);
                // Optional: set ghost image
            });
        }
    });

    map.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    });

    map.addEventListener('drop', (e) => {
        e.preventDefault();
        if (!draggedToken) return;

        const rect = map.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Snap to 32x32 grid
        const gridX = Math.min(31, Math.max(0, Math.floor(x / 32)));
        const gridY = Math.min(31, Math.max(0, Math.floor(y / 32)));
        
        updateTokenPosition(draggedToken, gridX, gridY);
        draggedToken = null;
    });
    
    function updateTokenPosition(token, x, y) {
        // Optimistic UI update
        token.style.left = (x * 32) + 'px';
        token.style.top = (y * 32) + 'px';
        token.dataset.x = x;
        token.dataset.y = y;
        
        const charId = token.dataset.id;
        
        // Send to backend
        fetch('move_token.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `char_id=${charId}&x=${x}&y=${y}`
        })
        .then(res => res.text())
        .then(data => {
            if (data.trim() !== 'success') {
                console.error('Movement failed:', data);
                // Revert? (Optional: fetch polling will fix it eventually)
            }
        });
    }

    // Polling for updates
    setInterval(() => {
        fetch(`api/game_state.php?campaign_id=${CAMPAIGN_ID}`)
            .then(res => res.json())
            .then(data => {
                data.forEach(char => {
                    const token = document.getElementById('token-' + char.char_id);
                    if (token && token !== draggedToken) { // Don't move if I'm dragging it
                         const currentX = parseInt(token.dataset.x);
                         const currentY = parseInt(token.dataset.y);
                         
                         // Only update if changed
                         if (currentX !== char.pos_x || currentY !== char.pos_y) {
                             token.style.left = (char.pos_x * 32) + 'px';
                             token.style.top = (char.pos_y * 32) + 'px';
                             token.dataset.x = char.pos_x;
                             token.dataset.y = char.pos_y;
                         }
                    } else if (!token) {
                        // New character joined? Refresh page or create element dynamically (not implemented for simplicity)
                    }
                });
            })
            .catch(err => console.error('Polling error:', err));
    }, 2000); // 2 seconds
});
