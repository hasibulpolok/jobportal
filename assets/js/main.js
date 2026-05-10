// ============================================================
// TalentBridge - Main JavaScript
// Developer: Hasibul Polok
// ============================================================

document.addEventListener('DOMContentLoaded', function () {

    // ---- Navbar scroll effect ----
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        navbar?.classList.toggle('scrolled', window.scrollY > 20);
    });

    // ---- Mobile nav toggle ----
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    navToggle?.addEventListener('click', () => {
        navMenu?.classList.toggle('open');
        const spans = navToggle.querySelectorAll('span');
        navMenu?.classList.contains('open')
            ? (spans[0].style.transform = 'rotate(45deg) translate(5px,5px)',
               spans[1].style.opacity = '0',
               spans[2].style.transform = 'rotate(-45deg) translate(5px,-5px)')
            : (spans[0].style.transform = '',
               spans[1].style.opacity = '',
               spans[2].style.transform = '');
    });

    // ---- User dropdown ----
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    userMenuBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown?.classList.toggle('open');
    });
    document.addEventListener('click', () => userDropdown?.classList.remove('open'));

    // ---- Flash message auto-dismiss ----
    const flash = document.querySelector('.flash');
    if (flash) {
        setTimeout(() => {
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-10px)';
            flash.style.transition = 'all 0.4s ease';
            setTimeout(() => flash.remove(), 400);
        }, 4500);
    }

    // ---- Active nav link ----
    const navLinks = document.querySelectorAll('.nav-link, .sidebar-nav a, .admin-sidebar a');
    navLinks.forEach(link => {
        if (link.href === window.location.href) link.classList.add('active');
    });

    // ---- Confirm delete ----
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) e.preventDefault();
        });
    });

    // ---- Modal system ----
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.dataset.modal;
            document.getElementById(modalId)?.classList.add('open');
        });
    });
    document.querySelectorAll('.modal-close, .modal-overlay').forEach(el => {
        el.addEventListener('click', (e) => {
            if (e.target === el) e.target.closest('.modal-overlay')?.classList.remove('open');
        });
    });

    // ---- CV file input preview ----
    const cvInput = document.getElementById('cv_file');
    const cvLabel = document.getElementById('cvLabel');
    cvInput?.addEventListener('change', () => {
        const file = cvInput.files[0];
        if (file) {
            const size = (file.size / 1024 / 1024).toFixed(2);
            if (cvLabel) cvLabel.textContent = `📎 ${file.name} (${size} MB)`;
        }
    });

    // ---- Character counter for textareas ----
    document.querySelectorAll('textarea[maxlength]').forEach(ta => {
        const max = ta.getAttribute('maxlength');
        const counter = document.createElement('small');
        counter.className = 'form-hint text-right';
        counter.style.float = 'right';
        ta.parentNode.appendChild(counter);
        const update = () => counter.textContent = `${ta.value.length}/${max}`;
        ta.addEventListener('input', update);
        update();
    });

    // ---- Animate stats on scroll ----
    const statNums = document.querySelectorAll('.stat-num, .hero-stat-num');
    const animateNum = (el) => {
        const target = parseInt(el.dataset.target || el.textContent.replace(/\D/g, '')) || 0;
        if (!target || el.dataset.animated) return;
        el.dataset.animated = '1';
        const suffix = el.textContent.replace(/[\d,]/g, '');
        let current = 0;
        const step = Math.ceil(target / 50);
        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = current.toLocaleString() + suffix;
            if (current >= target) clearInterval(timer);
        }, 30);
    };
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) animateNum(e.target); });
    }, { threshold: 0.5 });
    statNums.forEach(el => observer.observe(el));

    // ---- Salary toggle ----
    const salaryType = document.getElementById('salary_type');
    const salaryFields = document.getElementById('salaryFields');
    salaryType?.addEventListener('change', () => {
        if (salaryFields) salaryFields.style.display = salaryType.value === 'negotiable' ? 'none' : 'grid';
    });

    // ---- Form validation ----
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', (e) => {
            let valid = true;
            form.querySelectorAll('[required]').forEach(field => {
                field.classList.remove('is-invalid');
                const err = field.parentNode.querySelector('.form-error');
                if (err) err.remove();
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('is-invalid');
                    const errEl = document.createElement('span');
                    errEl.className = 'form-error';
                    errEl.textContent = 'This field is required.';
                    field.parentNode.appendChild(errEl);
                }
            });
            if (!valid) e.preventDefault();
        });
    });

    // ---- Save job toggle ----
    document.querySelectorAll('.save-job-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const jobId = btn.dataset.jobId;
            const saved = btn.dataset.saved === '1';
            try {
                const resp = await fetch(`${window.BASE_URL}/user/save-job.php`, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `job_id=${jobId}&action=${saved ? 'unsave' : 'save'}&csrf_token=${window.CSRF_TOKEN}`
                });
                const data = await resp.json();
                if (data.success) {
                    btn.dataset.saved = saved ? '0' : '1';
                    btn.textContent = saved ? '🤍 Save' : '❤️ Saved';
                    btn.title = saved ? 'Save this job' : 'Remove from saved';
                }
            } catch (err) { console.error(err); }
        });
    });

});
