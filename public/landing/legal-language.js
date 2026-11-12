document.addEventListener('DOMContentLoaded', () => {
  const languageSelect = document.querySelector('.legal-language-dropdown');
  const translatableElements = Array.from(document.querySelectorAll('[data-bn]'));
  const ariaTranslatableElements = Array.from(document.querySelectorAll('[data-bn-aria-label]'));
  const storageKey = 'inomanage-legal-language';

  function setLanguage(language) {
    const isBengali = language === 'বাংলা';
    document.documentElement.lang = isBengali ? 'bn' : 'en';

    translatableElements.forEach((element) => {
      if (!('englishText' in element.dataset)) {
        element.dataset.englishText = element.textContent;
      }

      element.textContent = isBengali ? element.dataset.bn : element.dataset.englishText;
    });

    ariaTranslatableElements.forEach((element) => {
      if (!('englishAriaLabel' in element.dataset)) {
        element.dataset.englishAriaLabel = element.getAttribute('aria-label');
      }

      element.setAttribute(
        'aria-label',
        isBengali ? element.dataset.bnAriaLabel : element.dataset.englishAriaLabel
      );
    });

    languageSelect.value = isBengali ? 'বাংলা' : 'English';

    window.localStorage.setItem(storageKey, isBengali ? 'বাংলা' : 'English');
  }

  languageSelect.addEventListener('change', () => setLanguage(languageSelect.value));

  const savedLanguage = window.localStorage.getItem(storageKey);
  setLanguage(savedLanguage === 'বাংলা' ? 'বাংলা' : 'English');
});
