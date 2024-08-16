"use strict";(self.webpackChunkdatakit_docs=self.webpackChunkdatakit_docs||[]).push([[339],{9773:(e,i,a)=>{a.r(i),a.d(i,{assets:()=>l,contentTitle:()=>r,default:()=>h,frontMatter:()=>n,metadata:()=>d,toc:()=>o});var t=a(4848),s=a(8453);const n={},r="GravatarField",d={id:"Fields/gravatar-field",title:"GravatarField",description:"The GravatarField renders a Gravatar image for an email address as an `` tag .",source:"@site/docs/Fields/26-gravatar-field.md",sourceDirName:"Fields",slug:"/Fields/gravatar-field",permalink:"/Fields/gravatar-field",draft:!1,unlisted:!1,editUrl:"https://github.com/UseDataKit/SDK/edit/main/docs/docs/Fields/26-gravatar-field.md",tags:[],version:"current",sidebarPosition:26,frontMatter:{},sidebar:"docs",previous:{title:"ImageField",permalink:"/Fields/image-field"},next:{title:"LinkField",permalink:"/Fields/link-field"}},l={},o=[{value:"Applying field settings",id:"applying-field-settings",level:2}];function c(e){const i={a:"a",admonition:"admonition",code:"code",h1:"h1",h2:"h2",header:"header",li:"li",p:"p",pre:"pre",ul:"ul",...(0,s.R)(),...e.components};return(0,t.jsxs)(t.Fragment,{children:[(0,t.jsx)(i.header,{children:(0,t.jsx)(i.h1,{id:"gravatarfield",children:"GravatarField"})}),"\n",(0,t.jsxs)(i.p,{children:["The ",(0,t.jsx)(i.code,{children:"GravatarField"})," renders a ",(0,t.jsx)(i.a,{href:"https://gravatar.com",children:"Gravatar"})," image for an email address as an ",(0,t.jsx)(i.code,{children:"<img />"})," tag ."]}),"\n",(0,t.jsx)(i.h2,{id:"applying-field-settings",children:"Applying field settings"}),"\n",(0,t.jsxs)(i.p,{children:["Under the hood, a ",(0,t.jsx)(i.code,{children:"GravatarField"})," renders like an ",(0,t.jsx)(i.code,{children:"ImageField"}),". This means it has all\nthe ",(0,t.jsx)(i.a,{href:"/Fields/image-field#applying-field-settings",children:"same image modifiers"})," as an ",(0,t.jsx)(i.code,{children:"ImageField"}),"."]}),"\n",(0,t.jsxs)(i.p,{children:["In addition to those modifiers, the ",(0,t.jsx)(i.code,{children:"GravatarField"})," also provides some modifiers of its own:"]}),"\n",(0,t.jsxs)(i.ul,{children:["\n",(0,t.jsxs)(i.li,{children:[(0,t.jsx)(i.code,{children:"->default_image( string $default )"})," Sets the ",(0,t.jsx)(i.a,{href:"https://docs.gravatar.com/api/avatars/images/#default-image",children:"default"}),"\nimage type for a missing avatar picture."]}),"\n",(0,t.jsxs)(i.li,{children:[(0,t.jsx)(i.code,{children:"->resolution( int $size )"})," Sets the ",(0,t.jsx)(i.a,{href:"https://docs.gravatar.com/api/avatars/images/#size",children:"resolution"})," of the image (\ndefault: 80)."]}),"\n"]}),"\n",(0,t.jsx)(i.p,{children:"A full example of this field:"}),"\n",(0,t.jsx)(i.pre,{children:(0,t.jsx)(i.code,{className:"language-php",children:"use DataKit\\DataViews\\Field\\GravatarField;\n\nGravatarField::create( 'email', 'Picture' )\n    ->resolution( 200 ) // Creates an image that is 200x200\n    ->default_image( 'retro' ) // Sets the images default to `retro` for a missing Gravatar picture.\n    ->size( 100 ) // Adds a `width=\"100\"` attribute to the image tag\n    ->alt( 'Profile picture for {name}' );\n"})}),"\n",(0,t.jsxs)(i.p,{children:["In this example you can notice that we also call the ",(0,t.jsx)(i.code,{children:"size()"})," and ",(0,t.jsx)(i.code,{children:"alt()"})," modifiers from an ",(0,t.jsx)(i.code,{children:"ImageField"}),"."]}),"\n",(0,t.jsx)(i.admonition,{type:"info",children:(0,t.jsxs)(i.p,{children:["The ",(0,t.jsx)(i.code,{children:"resolution"})," and ",(0,t.jsx)(i.code,{children:"size"})," are not the same thing. The ",(0,t.jsx)(i.code,{children:"resolution"})," is the size of the image that is used; while the\n",(0,t.jsx)(i.code,{children:"size"})," sets the ",(0,t.jsx)(i.code,{children:"width"})," (and ",(0,t.jsx)(i.code,{children:"height"}),") of the actual ",(0,t.jsx)(i.code,{children:"<img />"})," tag that is being rendered."]})})]})}function h(e={}){const{wrapper:i}={...(0,s.R)(),...e.components};return i?(0,t.jsx)(i,{...e,children:(0,t.jsx)(c,{...e})}):c(e)}},8453:(e,i,a)=>{a.d(i,{R:()=>r,x:()=>d});var t=a(6540);const s={},n=t.createContext(s);function r(e){const i=t.useContext(n);return t.useMemo((function(){return"function"==typeof e?e(i):{...i,...e}}),[i,e])}function d(e){let i;return i=e.disableParentContext?"function"==typeof e.components?e.components(s):e.components||s:r(e.components),t.createElement(n.Provider,{value:i},e.children)}}}]);