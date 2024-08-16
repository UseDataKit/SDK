"use strict";(self.webpackChunkmy_website=self.webpackChunkmy_website||[]).push([[319],{6979:(e,i,t)=>{t.r(i),t.d(i,{assets:()=>r,contentTitle:()=>a,default:()=>h,frontMatter:()=>d,metadata:()=>l,toc:()=>c});var s=t(4848),n=t(8453);const d={},a="ImageField",l={id:"Fields/image-field",title:"ImageField",description:"The ImageField renders the value of a data source as an `` tag.",source:"@site/docs/Fields/25-image-field.md",sourceDirName:"Fields",slug:"/Fields/image-field",permalink:"/Fields/image-field",draft:!1,unlisted:!1,editUrl:"https://github.com/UseDataKit/SDK/edit/main/docs/Fields/25-image-field.md",tags:[],version:"current",sidebarPosition:25,frontMatter:{},sidebar:"docs",previous:{title:"EnumField",permalink:"/Fields/enum-field"},next:{title:"GravatarField",permalink:"/Fields/gravatar-field"}},r={},c=[{value:"Applying field settings",id:"applying-field-settings",level:2}];function o(e){const i={a:"a",admonition:"admonition",code:"code",h1:"h1",h2:"h2",header:"header",li:"li",p:"p",pre:"pre",ul:"ul",...(0,n.R)(),...e.components};return(0,s.jsxs)(s.Fragment,{children:[(0,s.jsx)(i.header,{children:(0,s.jsx)(i.h1,{id:"imagefield",children:"ImageField"})}),"\n",(0,s.jsxs)(i.p,{children:["The ",(0,s.jsx)(i.code,{children:"ImageField"})," renders the value of a data source as an ",(0,s.jsx)(i.code,{children:"<img />"})," tag."]}),"\n",(0,s.jsx)(i.h2,{id:"applying-field-settings",children:"Applying field settings"}),"\n",(0,s.jsxs)(i.p,{children:["The ",(0,s.jsx)(i.code,{children:"ImageField"})," has a few other settings modifiers on top of\nthe ",(0,s.jsx)(i.a,{href:"/Fields/using-fields#applying-field-settings",children:"default modifiers"}),"."]}),"\n",(0,s.jsxs)(i.ul,{children:["\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"->size( int width, ?int height = null )"})," Adds a ",(0,s.jsx)(i.code,{children:'width=""'})," and ",(0,s.jsx)(i.code,{children:'height=""'})," attribute on the tag."]}),"\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"->class( string $class )"})," Adds the provided classes on the ",(0,s.jsx)(i.code,{children:'class=""'})," attribute."]}),"\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"->alt( string $alt)"})," Adds an alt text, with support for merge tags."]}),"\n"]}),"\n",(0,s.jsx)(i.p,{children:"Here is a full example:"}),"\n",(0,s.jsx)(i.pre,{children:(0,s.jsx)(i.code,{className:"language-php",children:"use DataKit\\DataViews\\Field\\ImageField;\n\nImageField::create( 'image', 'Image label' )\n    ->size( 300, 300 ) // Adds width=\"300\" height=\"300\" to the tag.\n    ->alt( 'Image for {name}.') // Adds an alt=\"Image for person\" attribute.\n    ->class( 'custom-class-1 custom-class-2'); // Adds `class=\"custom-class-1 custom-class-2\" to the tag.\n"})}),"\n",(0,s.jsx)(i.admonition,{type:"tip",children:(0,s.jsxs)(i.p,{children:["The ",(0,s.jsx)(i.code,{children:"alt"}),' text can reference the value of another field, by use of "merge tags". In this example we reference\nthe ',(0,s.jsx)(i.code,{children:"name"})," field by adding ",(0,s.jsx)(i.code,{children:"{name}"})," to the tag. This tag will be replaced by the value from the ",(0,s.jsx)(i.code,{children:"name"})," field."]})})]})}function h(e={}){const{wrapper:i}={...(0,n.R)(),...e.components};return i?(0,s.jsx)(i,{...e,children:(0,s.jsx)(o,{...e})}):o(e)}},8453:(e,i,t)=>{t.d(i,{R:()=>a,x:()=>l});var s=t(6540);const n={},d=s.createContext(n);function a(e){const i=s.useContext(d);return s.useMemo((function(){return"function"==typeof e?e(i):{...i,...e}}),[i,e])}function l(e){let i;return i=e.disableParentContext?"function"==typeof e.components?e.components(n):e.components||n:a(e.components),s.createElement(d.Provider,{value:i},e.children)}}}]);