document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.snooker-org-tab-btn');
    const contents = document.querySelectorAll('.snooker-org-tab-content');
    let currentIndex = 0;
    const intervalSeconds = 10;

    if (buttons.length === 0 || contents.length === 0) {
        console.warn('Tab buttons or contents not found, tabs will not work.');
    } else {
        setFixedWrapperHeight();
        updateButtons(currentIndex);

        function changeTab(newIndex) {
            if (newIndex === currentIndex) return;

            const currentTab = contents[currentIndex];
            const nextTab = contents[newIndex];

            contents.forEach(tabContent => {
                tabContent.classList.remove('exit-left', 'enter-right');
            });

            nextTab.classList.add('active', 'enter-right');
            currentTab.classList.add('exit-left');

            setTimeout(() => {
                currentTab.classList.remove('active', 'exit-left');
                nextTab.classList.remove('enter-right');
            }, 500);

            currentIndex = newIndex;
            updateButtons(newIndex);
        }

        function updateButtons(index) {
            buttons.forEach((btn, i) => btn.classList.toggle('active', i === index));
        }

        buttons.forEach((btn, i) => {
            btn.addEventListener('click', () => {
                changeTab(i);
                resetInterval();
            });
        });

        let autoSlideInterval = setInterval(() => {
            const nextIndex = (currentIndex + 1) % contents.length;
            changeTab(nextIndex);
        }, intervalSeconds * 1000);

        function resetInterval() {
            clearInterval(autoSlideInterval);
            autoSlideInterval = setInterval(() => {
                const nextIndex = (currentIndex + 1) % contents.length;
                changeTab(nextIndex);
            }, intervalSeconds * 1000);
        }

        function getMaxTabHeight() {
            let maxHeight = 0;
            contents.forEach(tab => {
                const originalStyles = {
                    position: tab.style.position,
                    visibility: tab.style.visibility,
                    display: tab.style.display
                };
                tab.style.position = 'static';
                tab.style.visibility = 'hidden';
                tab.style.display = 'block';

                const height = tab.offsetHeight;
                if (height > maxHeight) maxHeight = height;

                tab.style.position = originalStyles.position;
                tab.style.visibility = originalStyles.visibility;
                tab.style.display = originalStyles.display;
            });
            return maxHeight;
        }

        function setFixedWrapperHeight() {
            const wrapper = document.querySelector('.snooker-org-tab-wrapper');
            if (wrapper) {
                const logo = document.querySelector('.powered-by-wrapper');
                const logoHeight = logo ? logo.offsetHeight : 0;
                const maxHeight = getMaxTabHeight() + logoHeight;
                wrapper.style.height = maxHeight + 'px';
            }
        }

        // AJAX load for tab content
        function loadTabContent(tabIndex, action) {
            const target = contents[tabIndex];
            if (!target) return;

            target.innerHTML = '<div class="loading">Loading...</div>';

            const data = new URLSearchParams({
                action: action,
                security: snooker_ajax_object.nonce,
            });

            fetch(snooker_ajax_object.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data,
            })
                .then(response => response.text())
                .then(html => {
                    target.innerHTML = html;

                    setFixedWrapperHeight();
                })
                .catch(error => {
                    target.innerHTML = '<div class="error">Error loading content.</div>';
                    console.error('AJAX error:', error);
                });
        }

        // INITIAL: Only load tab 1 and 2 via AJAX after small delay
        setTimeout(() => loadTabContent(1, 'load_current_matches'), 5000);  // 5s after load
        setTimeout(() => loadTabContent(2, 'load_upcoming_matches'), 10000); // 10s after load

        // EVERY 10 minutes, reload ALL tabs via AJAX
        setInterval(() => {
            loadTabContent(0, 'load_previous_matches');
            loadTabContent(1, 'load_current_matches');
            loadTabContent(2, 'load_upcoming_matches');
        }, 600000); // 600,000 ms = 10 minutes
    }
});
