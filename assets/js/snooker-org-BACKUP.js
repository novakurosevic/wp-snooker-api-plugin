document.addEventListener('DOMContentLoaded', () => {

    const buttons = document.querySelectorAll('.snooker-org-tab-btn');
    const contents = document.querySelectorAll('.snooker-org-tab-content');
    const ajaxButton = document.querySelector('#your-ajax-button-id');
    let currentIndex = 0;
    const intervalSeconds = 1000;

    console.log('DOM fully loaded');

    // Check if tab buttons and contents exist
    if (buttons.length === 0 || contents.length === 0) {
        console.warn('Tab buttons or contents not found, tabs will not work.');
    } else {
        setFixedWrapperHeight();
        updateButtons(currentIndex); // initially highlight the first tab

        function changeTab(newIndex) {
            if (newIndex === currentIndex) return;

            const currentTab = contents[currentIndex];
            const nextTab = contents[newIndex];

            // Remove animation classes from all tabs
            contents.forEach(tabContent => {
                tabContent.classList.remove('exit-left', 'enter-right');
            });

            // Activate the next tab with enter animation
            nextTab.classList.add('active', 'enter-right');

            // Animate exit of the current tab
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
    } // end of tabs check

    // Separate code for ajaxButton, independent from tabs
    if (ajaxButton) {
        ajaxButton.addEventListener('click', () => {
            document.querySelector('#ajax-result').innerHTML = 'Loading...';
            // Your AJAX code can stay here or be further integrated

            const data = new URLSearchParams({
                action: 'snooker_org_ajax_action',
                security: snooker_ajax_object.nonce,
                some_value: 'Hello from JS',
            });

            fetch(snooker_ajax_object.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: data,
            })
                .then(response => response.text())
                .then(data => {
                    console.log('AJAX response:', data);
                    document.querySelector('#ajax-result').innerHTML = data;
                })
                .catch(error => console.error('AJAX error:', error));

        });
    } else {
        console.warn('AJAX button not found in DOM!');
    }
});
