// JetEngine - Blob random border radius
// Deze snippet geeft elementen met de class .blob-radius automatisch een willekeurige, organische (blob) border-radius vorm voor een speels design.

const applyBlobToSpecificContainer = () => {
  const containers = document.querySelectorAll('.blob-radius');

  containers.forEach(el => {
    const r = () => Math.floor(Math.random() * 50 + 25);
    const shape = `${r()}% ${r()}% ${r()}% ${r()}% / ${r()}% ${r()}% ${r()}% ${r()}%`;
   
    el.style.borderRadius = shape;
    el.style.overflow = 'hidden';

    el.style.webkitMaskImage = '-webkit-radial-gradient(white, black)';
  });
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', applyBlobToSpecificContainer);
} else {
  applyBlobToSpecificContainer();
}
