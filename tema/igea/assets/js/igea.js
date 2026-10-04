/* IGEA: comportamenti del tema. Nessuna libreria, nessun innerHTML.
   Ogni parte controlla che i suoi elementi esistano: lo stesso file serve la home e le pagine interne. */
(function(){
  'use strict';
  var doc=document.documentElement;
  var ridotto=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var fermo=false,heroP=0,alloScroll=[];
  try{fermo=localStorage.getItem('igea-pausa')==='1'}catch(e){}
  function limita(v,a,b){return Math.min(b,Math.max(a,v))}

  /* Sfera "second brain": nodi distribuiti su una sfera e collegati ai vicini, come un grafo di appunti.
     I nodi grandi sono le aree e le norme di IGEA. Ruota da sola e si trascina; il cursore la inclina, scosta e accende i nodi vicini; un clic manda un impulso.
     Canvas 2D, nessuna libreria. */
  function creaGlobo(cv){
    var ctx=cv.getContext('2d'),W=0,H=0,nodi=[],archi=[],segnali=[];
    var rotY=0.6,rotX=-0.28,vel=0.0018,trascina=null,mouse={x:-9999,y:-9999,attivo:false};
    /* hov: quanto il cursore è sulla sfera (0-1, smorzato); mx,my: cursore smorzato; incX,incY: inclinazione verso il cursore */
    var hov=0,mx=-9999,my=-9999,incX=0,incY=0,increspa=[],vic=[],ultimoP=null,heroBox=cv.parentNode;
    var ETICHETTE=['Sicurezza sul lavoro','Ambiente','Igiene degli alimenti','Medicina del lavoro','DVR','Formazione','D.Lgs. 81/08','HACCP','ATEX','ISO 9001','D.Lgs. 152/06','ISO 22000'];
    (function crea(){
      var N=150,oro=Math.PI*(3-Math.sqrt(5)),passoHub=Math.floor(N/ETICHETTE.length);
      for(var i=0;i<N;i++){var y=1-(i/(N-1))*2,r=Math.sqrt(1-y*y),t=oro*i,j=0.06;
        var x=Math.cos(t)*r+(Math.random()-.5)*j,z=Math.sin(t)*r+(Math.random()-.5)*j,yy=y+(Math.random()-.5)*j,l=Math.hypot(x,yy,z);
        var h=(i%passoHub===Math.floor(passoHub/2))&&Math.floor(i/passoHub)<ETICHETTE.length?Math.floor(i/passoHub):-1;
        var sd=[Math.random()-.5,Math.random()-.5,Math.random()-.5],sl=Math.hypot(sd[0],sd[1],sd[2])||1,sr=1.8+Math.random()*1.8;
        nodi.push({s:[sd[0]/sl*sr,sd[1]/sl*sr,sd[2]/sl*sr],p:[x/l,yy/l,z/l],hub:h>=0,t:h>=0?ETICHETTE[h]:'',verde:h>=0?h<4:Math.random()<.14,r:h>=0?(h<4?5.5:4):1.3+Math.random()*1.2});vic.push([])}
      for(var a=0;a<N;a++){var d=[];for(var b=0;b<N;b++)if(a!==b){var A=nodi[a].p,B=nodi[b].p;d.push([Math.hypot(A[0]-B[0],A[1]-B[1],A[2]-B[2]),b])}
        d.sort(function(u,v){return u[0]-v[0]});var k=nodi[a].hub?5:3;for(var c=0;c<k;c++){var bb=d[c][1];if(a<bb||!archi.some(function(e){return e[0]===bb&&e[1]===a}))archi.push([a,bb])}}
      for(var e=0;e<archi.length;e++){vic[archi[e][0]].push(e);vic[archi[e][1]].push(e)}
      for(var s=0;s<7;s++)segnali.push({e:Math.floor(Math.random()*archi.length),t:Math.random(),r:false,g:-1});
    })();
    var LIM=0;
    /* Misure senza la scala applicata allo scorrimento */
    function dimensiona(){var d=Math.min(window.devicePixelRatio||1,2),b=cv.getBoundingClientRect();W=cv.offsetWidth;H=cv.offsetHeight;LIM=(doc.clientWidth-8-b.left)*(W/(b.width||W));cv.width=W*d;cv.height=H*d;ctx.setTransform(d,0,0,d,0,0)}
    function proietta(p,R,cx,cy){var ry=rotY+incY,rx=rotX+incX,cy_=Math.cos(ry),sy=Math.sin(ry),x=p[0]*cy_-p[2]*sy,z=p[0]*sy+p[2]*cy_,cx_=Math.cos(rx),sx=Math.sin(rx),y=p[1]*cx_-z*sx;z=p[1]*sx+z*cx_;var f=1/Math.max(0.35,1-z*0.25);return [cx+x*R*f,cy+y*R*f,z,f]}
    var intro=0;
    function pos(n){if(intro>=1)return n.p;var e=1-Math.pow(1-intro,3);return [n.s[0]+(n.p[0]-n.s[0])*e,n.s[1]+(n.p[1]-n.s[1])*e,n.s[2]+(n.p[2]-n.s[2])*e]}
    function disegna(){
      var R=Math.min(W,H)*0.37,cx=W/2,cy=H/2,P=nodi.map(function(n){return proietta(pos(n),R,cx,cy)}),etich=W>=420&&intro>0.85,ia=intro*intro;
      /* Il cursore scosta i nodi vicini (lente) e li accende; le increspature accendono i nodi sull'onda */
      var RAD=R*0.66,L=new Array(P.length),spinta=(ridotto||fermo)?0:42*hov,i,q;
      for(i=0;i<P.length;i++){q=P[i];var l=0;if(hov>0.01&&q[2]>-0.35){var dx=q[0]-mx,dy=q[1]-my,dd=Math.hypot(dx,dy);if(dd<RAD){var k1=1-dd/RAD;l=k1*hov;if(dd>0.5){var pu=k1*k1*spinta;q[0]+=dx/dd*pu;q[1]+=dy/dd*pu}}}L[i]=l}
      for(var w=0;w<increspa.length;w++){var c=increspa[w],r0=c.t*R*1.2;for(i=0;i<P.length;i++){if(P[i][2]<-0.2)continue;var sc=Math.abs(Math.hypot(P[i][0]-c.x,P[i][1]-c.y)-r0);if(sc<28)L[i]=Math.max(L[i],(1-sc/28)*(1-c.t))}}
      ultimoP=P;
      ctx.clearRect(0,0,W,H);
      var g=ctx.createRadialGradient(cx,cy,R*0.1,cx,cy,R*1.3);g.addColorStop(0,'rgba(140,201,160,'+(0.10+hov*0.06)+')');g.addColorStop(.6,'rgba(40,69,102,0.05)');g.addColorStop(1,'rgba(255,255,255,0)');ctx.fillStyle=g;ctx.beginPath();ctx.arc(cx,cy,R*1.3,0,7);ctx.fill();
      /* Archi raggruppati per colore, spessore e trasparenza: poche chiamate stroke() per frame */
      var gr={};
      for(i=0;i<archi.length;i++){var a0=archi[i][0],b0=archi[i][1],A=P[a0],B=P[b0],z=(A[2]+B[2])/2,lu=(L[a0]+L[b0])/2,al=Math.round(((0.06+0.32*(z+1)/2)*ia+lu*0.55)*20)/20,v=(nodi[a0].verde&&nodi[b0].verde)||lu>0.25,lw=Math.round(((z>0?1:.6)+lu*0.8)*5)/5,ch=(v?'v':'b')+lw+'_'+al;
        if(al<=0)continue;(gr[ch]||(gr[ch]={c:(v?'rgba(79,157,105,':'rgba(40,69,102,')+al+')',w:lw,l:[]})).l.push(A[0],A[1],B[0],B[1])}
      for(var ch2 in gr){var gg=gr[ch2];ctx.strokeStyle=gg.c;ctx.lineWidth=gg.w;ctx.beginPath();for(var j=0;j<gg.l.length;j+=4){ctx.moveTo(gg.l[j],gg.l[j+1]);ctx.lineTo(gg.l[j+2],gg.l[j+3])}ctx.stroke()}
      /* Fili dal cursore ai nodi più vicini */
      if(hov>0.05&&intro>0.6){var vicini=[];for(i=0;i<P.length;i++){q=P[i];if(q[2]<0)continue;var dc=Math.hypot(q[0]-mx,q[1]-my);if(dc<RAD)vicini.push([dc,i])}
        vicini.sort(function(u,v){return u[0]-v[0]});ctx.lineWidth=.8;
        for(i=0;i<Math.min(7,vicini.length);i++){q=P[vicini[i][1]];ctx.strokeStyle='rgba(79,157,105,'+((1-vicini[i][0]/RAD)*0.6*hov)+')';ctx.beginPath();ctx.moveTo(mx,my);ctx.lineTo(q[0],q[1]);ctx.stroke()}
        ctx.fillStyle='rgba(79,157,105,'+(0.9*hov)+')';ctx.beginPath();ctx.arc(mx,my,3,0,7);ctx.fill();
        ctx.strokeStyle='rgba(79,157,105,'+(0.35*hov)+')';ctx.lineWidth=1;ctx.beginPath();ctx.arc(mx,my,11,0,7);ctx.stroke()}
      for(w=0;w<increspa.length;w++){c=increspa[w];ctx.strokeStyle='rgba(79,157,105,'+((1-c.t)*0.5)+')';ctx.lineWidth=1.4*(1-c.t)+.4;ctx.beginPath();ctx.arc(c.x,c.y,c.t*R*1.2,0,7);ctx.stroke()}
      for(var s=0;s<segnali.length;s++){var sg=segnali[s],e=archi[sg.e],A2=P[sg.r?e[1]:e[0]],B2=P[sg.r?e[0]:e[1]];if((A2[2]+B2[2])/2<-.1)continue;var x=A2[0]+(B2[0]-A2[0])*sg.t,y=A2[1]+(B2[1]-A2[1])*sg.t;ctx.fillStyle='rgba(79,157,105,.9)';ctx.beginPath();ctx.arc(x,y,sg.g<0?2.2:2.8,0,7);ctx.fill()}
      var ordine=P.map(function(q,i){return i}).sort(function(a,b){return P[a][2]-P[b][2]});
      ctx.font='600 12px Inter, system-ui, sans-serif';ctx.textBaseline='middle';
      var ingombri=etich?testiHero():[];
      for(var o=0;o<ordine.length;o++){var k=ordine[o],n=nodi[k],lk=L[k],dep=(P[k][2]+1)/2,rr;q=P[k];rr=n.r*q[3]*(1+lk*0.9);
        if(lk>0.15&&q[2]>-0.2){ctx.fillStyle='rgba(140,201,160,'+(lk*0.35)+')';ctx.beginPath();ctx.arc(q[0],q[1],rr*3,0,7);ctx.fill()}
        if(n.hub&&q[2]>-0.2){ctx.fillStyle=n.verde?'rgba(140,201,160,'+(.35*dep)+')':'rgba(40,69,102,'+(.14*dep)+')';ctx.beginPath();ctx.arc(q[0],q[1],rr*2.3,0,7);ctx.fill()}
        ctx.fillStyle=(n.verde||lk>0.3)?'rgba(79,157,105,'+Math.min(1,0.15+0.85*dep+lk)+')':'rgba(40,69,102,'+(0.12+0.8*dep)+')';ctx.beginPath();ctx.arc(q[0],q[1],rr,0,7);ctx.fill();
        if(n.hub&&etich&&(q[2]>0.05||lk>0.3)){var a2=Math.max(Math.min(1,(q[2]-0.05)*2.2)*.85,lk>0.2?1:0);var dx2=q[0]>cx?1:-1,tw=ctx.measureText(n.t).width;if(dx2>0&&q[0]+rr+16+tw>LIM)dx2=-1;
          var bx=dx2>0?q[0]+rr+6:q[0]-rr-12-tw;if(copre(bx,q[1]-9,tw+6,18,ingombri)){dx2=-dx2;bx=dx2>0?q[0]+rr+6:q[0]-rr-12-tw;if(copre(bx,q[1]-9,tw+6,18,ingombri)||(dx2>0&&bx+tw+6>LIM))continue}
          ctx.textAlign=dx2>0?'left':'right';ctx.fillStyle='rgba(255,255,255,'+(a2*.85)+')';ctx.fillRect(bx,q[1]-9,tw+6,18);
          ctx.fillStyle=lk>0.2?'rgba(40,69,102,'+a2+')':'rgba(27,43,64,'+a2+')';ctx.fillText(n.t,dx2>0?q[0]+rr+9:q[0]-rr-9,q[1])}}
    }
    /* Rettangoli dei testi della hero, in coordinate del canvas: le etichette non ci finiscono sopra */
    var testiSel=heroBox.querySelectorAll('.hero-testo .pillola,.hero-testo .riga>span,.hero-testo .lead,.hero-testo .btn');
    function testiHero(){var b=cv.getBoundingClientRect(),sc=W/(b.width||W),out=[];
      testiSel.forEach(function(el){var r=el.getBoundingClientRect();out.push([(r.left-b.left)*sc-6,(r.top-b.top)*sc-4,(r.right-b.left)*sc+6,(r.bottom-b.top)*sc+4])});return out}
    function copre(x,y,w,h,rr){for(var i=0;i<rr.length;i++){var r=rr[i];if(x<r[2]&&x+w>r[0]&&y<r[3]&&y+h>r[1])return true}return false}
    var raf=null;
    /* Velocità legata al tempo, non alla frequenza dello schermo (60 o 120 Hz) */
    var tPrec=0;
    function ciclo(tt){var dt=tPrec?Math.min(3,(tt-tPrec)/16.667):1,ease=function(k){return 1-Math.pow(1-k,dt)};tPrec=tt;
      if(intro<1)intro=Math.min(1,intro+0.0105*dt);
      var cx=W/2,cy=H/2,R=Math.min(W,H)*0.37,dentro=mouse.attivo&&Math.hypot(mouse.x-cx,mouse.y-cy)<R*1.45;
      hov+=((dentro?1:0)-hov)*ease(0.08);
      if(mouse.attivo){if(mx<-9000){mx=mouse.x;my=mouse.y}mx+=(mouse.x-mx)*ease(0.2);my+=(mouse.y-my)*ease(0.2)}
      var tX=mouse.attivo&&!trascina?limita((mouse.y-cy)/R,-1,1)*0.22:0,tY=mouse.attivo&&!trascina?limita((mouse.x-cx)/R,-1,1)*0.35:0;
      incX+=(tX-incX)*ease(0.05);incY+=(tY-incY)*ease(0.05);
      if(!trascina)rotY+=(vel*(1+heroP*5)*(1-hov*0.7)+(1-intro)*0.02)*dt;
      for(var s=segnali.length-1;s>=0;s--){var sg=segnali[s];sg.t+=(sg.g<0?0.012:0.03)*dt;if(sg.t<=1)continue;
        if(sg.g<0){sg.t=0;sg.r=false;sg.e=Math.floor(Math.random()*archi.length);continue}
        var fine=sg.r?archi[sg.e][0]:archi[sg.e][1];segnali.splice(s,1);
        if(sg.g<2&&segnali.length<90){var vv=vic[fine].filter(function(x){return x!==sg.e});for(var m=0;m<Math.min(2,vv.length);m++){var ee=vv[Math.floor(Math.random()*vv.length)];segnali.push({e:ee,t:0,r:archi[ee][0]!==fine,g:sg.g+1})}}}
      for(var w=increspa.length-1;w>=0;w--){increspa[w].t+=0.016*dt;if(increspa[w].t>=1)increspa.splice(w,1)}
      disegna();raf=requestAnimationFrame(ciclo)}
    function avvia(){if(raf)cancelAnimationFrame(raf);raf=null;tPrec=0;if(fermo||ridotto){intro=1;disegna()}else{raf=requestAnimationFrame(ciclo)}}
    /* Un clic o un tocco sulla sfera fa partire un'onda e degli impulsi dal nodo più vicino */
    function scatto(x,y){if(fermo||ridotto||!ultimoP||increspa.length>4)return;var best=-1,bd=1e9;
      for(var i=0;i<ultimoP.length;i++){var q=ultimoP[i];if(q[2]<0)continue;var d=Math.hypot(q[0]-x,q[1]-y);if(d<bd){bd=d;best=i}}
      if(best<0||bd>Math.min(W,H)*0.25)return;
      increspa.push({x:ultimoP[best][0],y:ultimoP[best][1],t:0});
      vic[best].forEach(function(e){segnali.push({e:e,t:0,r:archi[e][0]!==best,g:0})})}
    dimensiona();
    window.addEventListener('resize',function(){dimensiona();if(!raf)disegna()});
    function aMouse(e){var b=cv.getBoundingClientRect(),sc=W/(b.width||W);mouse.x=(e.clientX-b.left)*sc;mouse.y=(e.clientY-b.top)*sc}
    cv.addEventListener('pointerdown',function(e){aMouse(e);trascina={x:e.clientX,y:e.clientY,ry:rotY,rx:rotX,px:mouse.x,py:mouse.y,mosso:false};cv.setPointerCapture(e.pointerId)});
    heroBox.addEventListener('pointermove',function(e){if(e.pointerType==='touch'&&!trascina)return;aMouse(e);mouse.attivo=e.pointerType!=='touch';
      if(trascina){if(Math.abs(e.clientX-trascina.x)+Math.abs(e.clientY-trascina.y)>6)trascina.mosso=true;rotY=trascina.ry+(e.clientX-trascina.x)*0.006;rotX=limita(trascina.rx+(e.clientY-trascina.y)*0.004,-1.2,1.2)}
      if(!raf){var R=Math.min(W,H)*0.37;hov=mouse.attivo&&Math.hypot(mouse.x-W/2,mouse.y-H/2)<R*1.45?1:0;mx=mouse.x;my=mouse.y;disegna()}});
    cv.addEventListener('pointerup',function(){if(trascina&&!trascina.mosso)scatto(trascina.px,trascina.py);trascina=null});
    cv.addEventListener('pointercancel',function(){trascina=null});
    heroBox.addEventListener('pointerleave',function(){mouse.attivo=false;if(!raf){hov=0;disegna()}});
    /* Ferma l'animazione quando la hero non è visibile */
    if('IntersectionObserver' in window){new IntersectionObserver(function(v){if(v[0].isIntersecting)avvia();else if(raf){cancelAnimationFrame(raf);raf=null}}).observe(cv)}
    return avvia;
  }
  var cv=document.getElementById('grafo');
  var avviaGlobo=cv&&cv.getContext?creaGlobo(cv):function(){};

  /* Nastri di linee sottili, ripresi dai materiali IGEA: ogni nastro ha la sua fase (data-seme) */
  var NS='http://www.w3.org/2000/svg';
  document.querySelectorAll('svg.onde').forEach(function(svg){
    var vb=svg.viewBox&&svg.viewBox.baseVal,lw=vb&&vb.width?vb.width:720,se=+(svg.getAttribute('data-seme')||0),n=+(svg.getAttribute('data-linee')||46),bianco=svg.getAttribute('data-tono')==='bianco',fr=120+se*9,frag=document.createDocumentFragment();
    for(var i=0;i<n;i++){var t=i/(n-1),d='';
      for(var x=0;x<=lw;x+=12){var y=210+Math.sin(x/fr+t*2.4+se)*(70+t*60)+Math.cos(x/(fr/2)-t*3+se*0.7)*18*t-(t-.5)*90;d+=(x?'L':'M')+x+' '+y.toFixed(1)}
      var verde=i>n*0.58&&i<n*0.83,col=bianco?(verde?'rgba(140,201,160,.75)':'rgba(255,255,255,.2)'):(verde?'rgba(79,157,105,.55)':'rgba(40,69,102,.22)');
      var p=document.createElementNS(NS,'path');
      p.setAttribute('pathLength','1');p.setAttribute('d',d);p.setAttribute('fill','none');p.setAttribute('stroke',col);p.setAttribute('stroke-width','.7');
      p.style.setProperty('--rit',(i*22)+'ms');frag.appendChild(p)}
    svg.textContent='';svg.appendChild(frag)});

  /* Pausa animazioni (WCAG 2.2.2), scelta ricordata */
  var pause=document.querySelectorAll('[data-pausa]');
  function applicaPausa(){doc.classList.toggle('ferma',fermo);pause.forEach(function(b){b.textContent=fermo?'Riprendi animazioni':'Pausa animazioni'});avviaGlobo()}
  pause.forEach(function(b){b.addEventListener('click',function(){fermo=!fermo;try{localStorage.setItem('igea-pausa',fermo?'1':'0')}catch(e){}applicaPausa()})});
  applicaPausa();

  /* Header pieno dopo i primi pixel */
  var testata=document.getElementById('testata');
  if(testata)alloScroll.push(function(){testata.classList.toggle('pieno',window.scrollY>40)});

  /* Servizi orizzontali: lo scorrimento verticale muove i pannelli di lato */
  var oriz=document.getElementById('oriz'),traccia=document.getElementById('traccia');
  if(oriz&&traccia){
    var orizBarra=document.getElementById('oriz-barra'),orizFoto=traccia.querySelectorAll('.pannello-foto img'),orizMax=0,orizAttivo=false,nastroOriz=document.getElementById('nastro-oriz');
    var misuraOriz=function(){orizAttivo=window.matchMedia('(min-width: 901px) and (min-height: 681px)').matches&&!ridotto;
      if(!orizAttivo){oriz.style.height='';traccia.style.transform='';if(nastroOriz)nastroOriz.style.transform='';orizFoto.forEach(function(i){i.style.removeProperty('--px')});return}
      orizMax=Math.max(0,traccia.scrollWidth-doc.clientWidth);oriz.style.height=(orizMax+window.innerHeight)+'px'};
    var orizzontale=function(){if(!orizAttivo)return;var b=oriz.getBoundingClientRect(),tot=b.height-window.innerHeight,p=tot>0?limita(-b.top/tot,0,1):0;
      traccia.style.transform='translate3d('+(-orizMax*p).toFixed(1)+'px,0,0)';if(nastroOriz)nastroOriz.style.transform='translate3d('+(-orizMax*p*0.35).toFixed(1)+'px,0,0)';if(orizBarra)orizBarra.style.setProperty('--p',p.toFixed(3));
      var cx=window.innerWidth/2;orizFoto.forEach(function(img){var r=img.parentNode.getBoundingClientRect(),off=(r.left+r.width/2-cx)/window.innerWidth;img.style.setProperty('--px',(off*-60).toFixed(1)+'px')})};
    misuraOriz();window.addEventListener('resize',function(){misuraOriz();orizzontale()});
    alloScroll.push(orizzontale);
    /* Con la tastiera il pannello che riceve il focus viene portato in vista */
    traccia.addEventListener('focusin',function(e){if(!orizAttivo)return;var p=e.target.closest('.pannello'),fisso=traccia.parentNode;if(!p)return;fisso.scrollLeft=0;
      var x=p.offsetLeft+p.offsetWidth/2-doc.clientWidth/2,pr=orizMax?limita(x/orizMax,0,1):0;
      window.scrollTo({top:oriz.getBoundingClientRect().top+window.scrollY+(oriz.offsetHeight-window.innerHeight)*pr,behavior:'instant'})});
  }

  /* La dea Igea si disegna tratto per tratto mentre la sezione entra in vista */
  var deaSvg=document.getElementById('dea-svg');
  if(deaSvg&&!ridotto){
    deaSvg.style.setProperty('--d','0');
    alloScroll.push(function(){var b=deaSvg.getBoundingClientRect(),h=window.innerHeight,p=(h*0.92-b.top)/(h*0.5+b.height*0.55);deaSvg.style.setProperty('--d',limita(p,0,1).toFixed(3))});
  }

  /* Hero: allo scorrimento la sfera si ingrandisce e il testo sale */
  var heroEl=document.querySelector('.hero'),heroTesto=document.querySelector('.hero-testo');
  if(heroEl&&heroTesto){
    alloScroll.push(function(){var h=heroEl.offsetHeight;heroP=limita(window.scrollY/h,0,1);if(ridotto)return;
      if(cv)cv.style.setProperty('--hp',heroP.toFixed(3));
      heroTesto.style.transform='translateY('+(-heroP*80).toFixed(1)+'px)';heroTesto.style.opacity=(1-heroP*1.3).toFixed(3)});
  }

  /* Metodo: la linea si riempie e i passi si accendono man mano che si scorre */
  var linea=document.getElementById('linea');
  if(linea){
    var passi=linea.querySelectorAll('.passo');
    alloScroll.push(function(){var b=linea.getBoundingClientRect(),h=window.innerHeight,p=ridotto?1:limita((h*0.6-b.top)/b.height,0,1);linea.style.setProperty('--p',p.toFixed(3));
      passi.forEach(function(el){el.classList.toggle('on',ridotto||el.getBoundingClientRect().top<h*0.6)})});
  }

  /* Manifesto che si accende parola per parola: il testo intero resta per gli screen reader */
  var man=document.getElementById('manifesto');
  if(man){
    var testo=man.textContent.trim(),vh=document.createElement('span'),vis=document.createElement('span');
    vh.className='vh';vh.textContent=testo;vis.setAttribute('aria-hidden','true');
    testo.split(/\s+/).forEach(function(w,i){var s=document.createElement('span');s.className='p'+(/prevenire|rimediare/.test(w)?' verde':'');s.textContent=w;if(i)vis.appendChild(document.createTextNode(' '));vis.appendChild(s)});
    man.textContent='';man.append(vh,vis);
    var ps=man.querySelectorAll('.p');
    alloScroll.push(function(){var b=man.getBoundingClientRect(),h=window.innerHeight,p=limita((h*0.9-b.top)/(b.height+h*0.3),0,1);var lim=ridotto?ps.length:Math.round(p*ps.length);for(var i=0;i<ps.length;i++)ps[i].classList.toggle('on',i<lim)});
  }

  function suScroll(){for(var i=0;i<alloScroll.length;i++)alloScroll[i]()}
  window.addEventListener('scroll',suScroll,{passive:true});
  suScroll();

  /* Comparsa allo scorrimento e contatori: armati solo con JS e senza movimento ridotto */
  if('IntersectionObserver' in window&&!ridotto){
    doc.classList.add('armato');
    var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting){e.target.classList.add('vista');io.unobserve(e.target)}})},{threshold:.12,rootMargin:'0px 0px -6% 0px'});
    document.querySelectorAll('.rivela,.onde').forEach(function(el){io.observe(el)});
    var ic=new IntersectionObserver(function(es){es.forEach(function(e){if(!e.isIntersecting)return;ic.unobserve(e.target);var el=e.target,fine=+el.getAttribute('data-conta'),t0=null;
      function passo(t){if(!t0)t0=t;var p=Math.min(1,(t-t0)/1200),v=Math.round(fine*(1-Math.pow(1-p,3)));el.textContent=v;if(p<1)requestAnimationFrame(passo)}
      el.textContent='0';requestAnimationFrame(passo)})},{threshold:.6});
    document.querySelectorAll('[data-conta]').forEach(function(el){ic.observe(el)});
  }

  /* Test "Quali adempimenti ti riguardano?": tutto nel browser, nessun dato inviato finché non si usa il modulo */
  (function(){
    var f=document.getElementById('test');if(!f)return;
    var domande=f.querySelectorAll('.domanda'),passi=f.querySelectorAll('.test-passi li'),err=document.getElementById('test-errore'),
      avanti=document.getElementById('test-avanti'),indietro=document.getElementById('test-indietro'),azioni=document.getElementById('test-azioni'),
      esito=document.getElementById('test-esito'),lista=document.getElementById('esiti'),cur=1,storia=[],riepilogo=[];
    f.hidden=false;
    function val(n){var c=f.querySelector('input[name="'+n+'"]:checked');return c?c.value:''}
    function vals(n){return Array.prototype.map.call(f.querySelectorAll('input[name="'+n+'"]:checked'),function(i){return i.value})}
    /* Domanda 4: solo le voci che servono per le risposte date */
    function aggiorna4(){var si=val('lavoratori')==='si',al=vals('attivita').indexOf('alimenti')>=0,n=0;
      f.querySelectorAll('.domanda[data-passo="4"] .scelta').forEach(function(l){var vis=l.getAttribute('data-serve')==='si'?si:al;l.hidden=!vis;if(!vis)l.querySelector('input').checked=false;else n++});return n}
    function vai(p,focus){cur=p;domande.forEach(function(d){d.hidden=+d.getAttribute('data-passo')!==p});
      passi.forEach(function(li,i){li.classList.toggle('on',i<p)});indietro.hidden=!storia.length;err.textContent='';
      avanti.firstChild.nodeValue=p===4?'Vedi il risultato ':'Avanti ';
      if(focus){var i=f.querySelector('.domanda[data-passo="'+p+'"] input');if(i)i.focus()}}
    function prossimo(p){if(p===1)return val('lavoratori')==='si'?2:3;if(p===2)return 3;if(p===3)return aggiorna4()?4:0;return 0}
    /* "Nessuna di queste" esclude le altre risposte */
    f.addEventListener('change',function(e){var t=e.target;if(t.name!=='attivita')return;
      f.querySelectorAll('input[name="attivita"]').forEach(function(i){if(i!==t&&t.checked&&(t.value==='nessuna'||i.value==='nessuna'))i.checked=false})});
    f.addEventListener('submit',function(e){e.preventDefault();
      var msg=(cur===1&&!val('lavoratori'))||(cur===2&&!val('quante'))?'Scegli una risposta.':(cur===3&&!vals('attivita').length)?'Scegli almeno una risposta.':'';
      if(msg){err.textContent=msg;return}
      var n=prossimo(cur);storia.push(cur);if(n)vai(n,true);else risultato()});
    indietro.addEventListener('click',function(){var p=storia.pop();if(p)vai(p,true)});
    function risultato(){var si=val('lavoratori')==='si',q=val('quante'),at=vals('attivita'),gia=vals('gia'),voci=[];
      function v(t,testo,norma,chiave){voci.push({t:t,testo:testo,norma:norma,fatto:!!chiave&&gia.indexOf(chiave)>=0})}
      if(si){
        v('Documento di valutazione dei rischi (DVR)','Obbligatorio dal primo lavoratore, da aggiornare quando cambia qualcosa di significativo.','D.Lgs. 81/08, artt. 17, 28 e 29','dvr');
        v('Nomina dell’RSPP','La fa il datore di lavoro; in alcuni casi può svolgerne lui stesso i compiti, dopo un corso specifico.','D.Lgs. 81/08, artt. 17 e 34','rspp');
        v('Formazione sulla sicurezza','Per lavoratori, preposti, dirigenti e per te, con aggiornamenti periodici.','D.Lgs. 81/08, art. 37','formazione');
        v('Addetti antincendio e primo soccorso','Da designare e formare.','D.Lgs. 81/08, art. 18');
        v('Sorveglianza sanitaria','Obbligatoria quando la valutazione individua rischi che la richiedono: allora serve il medico competente.','D.Lgs. 81/08, art. 41','medico');
        if(q&&q!=='1-15')v('Riunione periodica','Almeno una volta l’anno, oltre i 15 lavoratori.','D.Lgs. 81/08, art. 35');
        if(at.indexOf('agenti')>=0)v('Valutazioni con misure strumentali','Rumore, vibrazioni e agenti chimici si valutano misurando.','D.Lgs. 81/08, Titoli VIII e IX');
      }else v('Senza lavoratori','Il D.Lgs. 81/08 si applica in forma ridotta: restano gli obblighi legati all’attività e alle attrezzature.','D.Lgs. 81/08, art. 21');
      if(at.indexOf('alimenti')>=0)v('Autocontrollo HACCP','Piano basato sui principi HACCP e formazione di chi manipola alimenti.','Reg. (CE) 852/2004','haccp');
      if(at.indexOf('ambiente')>=0)v('Adempimenti ambientali','Autorizzazioni, registri e gestione dei rifiuti, secondo la tua attività.','D.Lgs. 152/06');
      lista.textContent='';
      voci.forEach(function(x,i){var li=document.createElement('li'),s=document.createElement('span'),d=document.createElement('div'),b=document.createElement('strong'),st=document.createElement('span'),p=document.createElement('p'),no=document.createElement('span');
        if(x.fatto)li.className='fatto';s.className='segno';s.setAttribute('aria-hidden','true');s.textContent=x.fatto?'✓':String(i+1);
        b.textContent=x.t;st.className='stato';st.textContent=x.fatto?'Hai indicato di averlo: verifichiamo che sia aggiornato':'Da fare o da verificare';
        p.textContent=x.testo;no.className='norma';no.textContent=x.norma;d.append(b,st,p,no);li.append(s,d);lista.appendChild(li)});
      riepilogo=voci;domande.forEach(function(d){d.hidden=true});azioni.hidden=true;err.textContent='';esito.hidden=false;
      passi.forEach(function(li){li.classList.add('on')});document.getElementById('t-esito').focus()}
    document.getElementById('test-rifai').addEventListener('click',function(){f.reset();storia=[];riepilogo=[];esito.hidden=true;azioni.hidden=false;vai(1,true)});
    /* Il risultato precompila il modulo contatti della stessa pagina: nulla parte finché la persona non invia */
    document.getElementById('test-invia').addEventListener('click',function(e){
      var area=document.getElementById('area'),persone=document.getElementById('persone'),msg=document.getElementById('messaggio'),nome=document.getElementById('nome'),sez=document.getElementById('contatti');
      if(!area||!msg||!nome||!sez)return;
      e.preventDefault();
      var si=val('lavoratori')==='si',at=vals('attivita');
      area.value=si?'sicurezza':at.indexOf('alimenti')>=0?'alimenti':at.indexOf('ambiente')>=0?'ambiente':'altro';
      if(persone)persone.value=si?val('quante'):'';
      msg.value=('Risultato del test sul sito:\n'+riepilogo.map(function(x){return '- '+x.t+': '+(x.fatto?'già presente, da verificare':'da fare o da verificare')}).join('\n')).slice(0,3000);
      sez.scrollIntoView({behavior:ridotto?'auto':'smooth'});nome.focus({preventScroll:true})});
  })();

  /* I link con data-area preselezionano l'area nel modulo contatti della stessa pagina */
  var areaSel=document.getElementById('area');
  if(areaSel)document.querySelectorAll('a[data-area]').forEach(function(a){a.addEventListener('click',function(){areaSel.value=a.getAttribute('data-area')})});

  /* Menu mobile */
  var mb=document.getElementById('menu-btn'),menu=document.getElementById('menu');
  if(mb&&menu){
    var chiudi=function(){menu.classList.remove('aperto');mb.setAttribute('aria-expanded','false')};
    mb.addEventListener('click',function(){var a=menu.classList.toggle('aperto');mb.setAttribute('aria-expanded',String(a));var primo=menu.querySelector('a');if(a&&primo)primo.focus()});
    document.addEventListener('keydown',function(e){if(e.key==='Escape'&&menu.classList.contains('aperto')){chiudi();mb.focus()}});
    menu.addEventListener('click',function(e){if(e.target.closest('a'))chiudi()});
    menu.addEventListener('focusout',function(e){if(menu.classList.contains('aperto')&&!menu.contains(e.relatedTarget)&&e.relatedTarget!==mb)chiudi()});
    document.addEventListener('click',function(e){if(menu.classList.contains('aperto')&&!menu.contains(e.target)&&!mb.contains(e.target))chiudi()});
  }

  /* Modulo contatti: controllo nel browser prima dell'invio; il controllo vero lo rifà il server */
  var f=document.getElementById('modulo');
  if(f){
    var invia=f.querySelector('button[type="submit"]'),inviato=false;
    f.addEventListener('submit',function(e){
      if(inviato){e.preventDefault();return}
      f.querySelectorAll('.errore').forEach(function(x){x.remove()});
      f.querySelectorAll('[aria-invalid]').forEach(function(x){x.removeAttribute('aria-invalid');var d=(x.getAttribute('aria-describedby')||'').split(' ').filter(function(id){return id&&id.indexOf('err-')!==0}).join(' ');if(d)x.setAttribute('aria-describedby',d);else x.removeAttribute('aria-describedby')});
      var primo=null;
      [['nome','Scrivi nome e cognome.'],['email','Scrivi un indirizzo email valido.'],['privacy','Conferma di aver letto l’informativa privacy.']].forEach(function(c){
        var el=document.getElementById(c[0]);if(!el||el.checkValidity())return;el.setAttribute('aria-invalid','true');var m=document.createElement('span');m.className='errore';m.id='err-'+c[0];m.textContent=c[1];
        el.setAttribute('aria-describedby',((el.getAttribute('aria-describedby')||'')+' '+m.id).trim());(el.type==='checkbox'?el.parentNode:el).insertAdjacentElement('afterend',m);if(!primo)primo=el});
      if(primo){e.preventDefault();primo.focus();return}
      inviato=true;if(invia){invia.setAttribute('aria-disabled','true');invia.firstChild.nodeValue='Invio in corso… '}});
    /* L'errore sparisce appena il campo è corretto */
    f.addEventListener('input',function(e){var el=e.target,m=document.getElementById('err-'+el.id);
      if(m&&el.checkValidity()){m.remove();el.removeAttribute('aria-invalid');var d=(el.getAttribute('aria-describedby')||'').split(' ').filter(function(id){return id&&id!==m.id}).join(' ');if(d)el.setAttribute('aria-describedby',d);else el.removeAttribute('aria-describedby')}});
    /* Torna utilizzabile se la pagina viene ripresa dalla cache del browser (tasto Indietro) */
    window.addEventListener('pageshow',function(){inviato=false;if(invia){invia.removeAttribute('aria-disabled');invia.firstChild.nodeValue='Invia la richiesta '}});
  }

  /* Dopo un invio o un errore lato server il messaggio riceve il focus.
     Dopo il load: lo scorrimento del browser all'ancora #contatti toglierebbe il focus dato prima. */
  var avviso=document.querySelector('[data-focus-avviso]');
  if(avviso){
    var focalizza=function(){setTimeout(function(){avviso.scrollIntoView({block:'center'});avviso.focus({preventScroll:true})},0)};
    if(document.readyState==='complete')focalizza();else window.addEventListener('load',focalizza);
  }
})();
