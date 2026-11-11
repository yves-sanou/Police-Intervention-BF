const CACHE_NAME="police-bf-v5-pwa-v2";
const BASE = new URL("./", self.location.href).pathname;
const SHELL=[BASE,BASE+"index.php",BASE+"assets/css/landing.css",BASE+"assets/js/landing.js",BASE+"manifest.json"];

self.addEventListener("install",e=>{
  e.waitUntil(caches.open(CACHE_NAME).then(c=>c.addAll(SHELL)).then(()=>self.skipWaiting()));
});
self.addEventListener("activate",e=>{
  e.waitUntil(caches.keys().then(keys=>Promise.all(keys.filter(k=>k!==CACHE_NAME).map(k=>caches.delete(k)))).then(()=>self.clients.claim()));
});
self.addEventListener("fetch",e=>{
  if(e.request.method!=="GET") return;
  e.respondWith(caches.match(e.request).then(cached=>{
    if(cached) return cached;
    return fetch(e.request).then(response=>{
      if(response && response.status===200 && response.type!=="opaque"){
        const copy=response.clone();
        caches.open(CACHE_NAME).then(c=>c.put(e.request,copy));
      }
      return response;
    }).catch(()=>e.request.mode==="navigate"?caches.match(BASE):new Response("",{status:503}));
  }));
});
