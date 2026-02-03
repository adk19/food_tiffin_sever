<?php if (admin_is_authed()): ?>
      </section>
    </main>
  </div>
  <script>
    (function(){
      const sidebar = document.querySelector('.sidebar');
      const toggle = document.getElementById('sidebarToggle');
      const overlay = document.getElementById('sidebarOverlay');
      if(!sidebar || !toggle || !overlay) return;

      const open = () => {
        sidebar.classList.add('open');
        document.body.classList.add('no-scroll');
        overlay.setAttribute('aria-hidden','false');
      };
      const close = () => {
        sidebar.classList.remove('open');
        document.body.classList.remove('no-scroll');
        overlay.setAttribute('aria-hidden','true');
      };

      toggle.addEventListener('click', (e)=>{
        e.preventDefault();
        if (sidebar.classList.contains('open')) close(); else open();
      });
      overlay.addEventListener('click', close);
      document.addEventListener('keydown', (e)=>{ if(e.key==='Escape') close(); });
      // Close after clicking a nav link (mobile UX)
      sidebar.addEventListener('click', (e)=>{
        const a = e.target.closest('a.nav-link');
        if (a && window.matchMedia('(max-width: 720px)').matches) close();
      });
      // Reset state on resize (desktop -> ensure sidebar visible and body scroll restored)
      const mq = window.matchMedia('(min-width: 721px)');
      const handle = () => { if (mq.matches) { close(); } };
      mq.addEventListener ? mq.addEventListener('change', handle) : mq.addListener(handle);

      // Modal: live image preview for file inputs
      document.addEventListener('change', (e)=>{
        const input = e.target;
        if (!(input instanceof HTMLInputElement)) return;
        if (input.type !== 'file') return;
        const modal = input.closest('.modal');
        if (!modal) return;
        const preview = modal.querySelector('.img-preview');
        if (!preview) return;
        const file = input.files && input.files[0];
        if (!file) { preview.innerHTML = '<span class="help">No image</span>'; return; }
        const reader = new FileReader();
        reader.onload = () => { preview.innerHTML = `<img src="${reader.result}" alt="Preview" />`; };
        reader.readAsDataURL(file);
      });

      // Dashboard: Order status chart (safe init)
      try {
        const canvas = document.getElementById('orderStatusChart');
        const data = (window && window.dashboardData) ? window.dashboardData : null;
        if (canvas && window.Chart && data) {
          const ctx = canvas.getContext('2d');
          const bg = [
            'rgba(245, 158, 11, 0.6)',   // pending - amber
            'rgba(34, 197, 94, 0.6)',    // confirmed - green
            'rgba(59, 130, 246, 0.6)',   // delivered - blue
            'rgba(239, 68, 68, 0.6)'     // cancelled - red
          ];
          const border = bg.map(c => c.replace('0.6', '1'));
          new Chart(ctx, {
            type: 'doughnut',
            data: {
              labels: ['Pending', 'Confirmed', 'Delivered', 'Cancelled'],
              datasets: [{
                data: [data.pending||0, data.confirmed||0, data.delivered||0, data.cancelled||0],
                backgroundColor: bg,
                borderColor: border,
                borderWidth: 1,
              }]
            },
            options: {
              plugins: {
                legend: { position: 'bottom', labels: { color: '#cbd5e1' } },
              }
            }
          });
        }
      } catch (err) { /* ignore rendering issues */ }
    })();
  </script>
<?php else: ?>
</div>
<?php endif; ?>
</body>
</html>
