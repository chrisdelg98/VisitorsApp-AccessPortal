{{--
    Global lightbox bootstrap. Loaded once via renderHook in AdminPanelProvider.
    Exposes window.eflOpen(url) and window.eflClose() for any component to call.
--}}
<style>
@keyframes eflFadeIn  { from{opacity:0}                      to{opacity:1} }
@keyframes eflFadeOut { from{opacity:1}                      to{opacity:0} }
@keyframes eflZoomIn  { from{opacity:0;transform:scale(.88)} to{opacity:1;transform:scale(1)} }
@keyframes eflZoomOut { from{opacity:1;transform:scale(1)}   to{opacity:0;transform:scale(.95)} }
#efl-lightbox          { display:flex;align-items:center;justify-content:center; }
#efl-lightbox.efl-in   { animation:eflFadeIn  .22s cubic-bezier(.16,1,.3,1) both; }
#efl-lightbox.efl-out  { animation:eflFadeOut .18s ease-in both; }
#efl-lightbox img.efl-in  { animation:eflZoomIn  .26s cubic-bezier(.16,1,.3,1) both; }
#efl-lightbox img.efl-out { animation:eflZoomOut .18s ease-in both; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window._eflLb) return;
    window._eflLb = true;

    var ov  = document.createElement('div');
    var img = document.createElement('img');
    var btn = document.createElement('button');

    ov.id = 'efl-lightbox';
    ov.style.cssText =
        'display:none;position:fixed;top:0;left:0;width:100vw;height:100vh;' +
        'z-index:99999;background:rgba(0,0,0,.82);' +
        '-webkit-backdrop-filter:blur(6px);backdrop-filter:blur(6px);';

    img.style.cssText =
        'max-width:88vw;max-height:88vh;border-radius:10px;display:block;' +
        'box-shadow:0 25px 60px rgba(0,0,0,.6);object-fit:contain;';

    btn.innerHTML = '✕';
    btn.title     = 'Close (Esc)';
    btn.style.cssText =
        'position:absolute;top:16px;right:16px;z-index:10;cursor:pointer;' +
        'background:rgba(30,30,30,.7);border:2px solid rgba(255,255,255,.55);' +
        'border-radius:50%;width:44px;height:44px;color:#fff;' +
        'font-size:18px;font-weight:700;display:flex;align-items:center;' +
        'justify-content:center;box-shadow:0 2px 12px rgba(0,0,0,.5);' +
        'transition:background .2s,border-color .2s;';
    btn.onmouseover = function(){ btn.style.background='rgba(255,255,255,.25)'; btn.style.borderColor='#fff'; };
    btn.onmouseout  = function(){ btn.style.background='rgba(30,30,30,.7)';     btn.style.borderColor='rgba(255,255,255,.55)'; };

    ov.appendChild(btn);
    ov.appendChild(img);
    document.body.appendChild(ov);

    window.eflOpen = function (url) {
        img.src = url;
        ov.style.display = '';
        void ov.offsetWidth;
        ov.classList.remove('efl-out');  img.classList.remove('efl-out');
        ov.classList.add('efl-in');      img.classList.add('efl-in');
        document.body.style.overflow = 'hidden';
    };

    window.eflClose = function () {
        ov.classList.remove('efl-in');  img.classList.remove('efl-in');
        ov.classList.add('efl-out');    img.classList.add('efl-out');
        document.body.style.overflow = '';
        setTimeout(function () {
            ov.style.display = 'none';
            ov.classList.remove('efl-out');
            img.classList.remove('efl-out');
        }, 220);
    };

    btn.onclick = window.eflClose;
    ov.addEventListener('click', function (e) { if (e.target === ov) window.eflClose(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') window.eflClose(); });
});
</script>
