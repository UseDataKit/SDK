"use strict";(self.webpackChunkdatakit_docs=self.webpackChunkdatakit_docs||[]).push([[811],{4689:(e,t,i)=>{i.r(t),i.d(t,{assets:()=>a,contentTitle:()=>d,default:()=>h,frontMatter:()=>l,metadata:()=>o,toc:()=>r});var n=i(4848),s=i(8453);const l={},d="TextField",o={id:"SDK/Fields/text-field",title:"TextField",description:"The TextField is the most basic field that DataKit provides. It will show the value for the field, without any",source:"@site/docs/SDK/Fields/11-text-field.md",sourceDirName:"SDK/Fields",slug:"/SDK/Fields/text-field",permalink:"/SDK/Fields/text-field",draft:!1,unlisted:!1,editUrl:"https://github.com/UseDataKit/SDK/edit/main/docs/docs/SDK/Fields/11-text-field.md",tags:[],version:"current",sidebarPosition:11,frontMatter:{},sidebar:"docs",previous:{title:"Introduction to fields",permalink:"/SDK/Fields/using-fields"},next:{title:"HtmlField",permalink:"/SDK/Fields/html-field"}},a={},r=[{value:"Applying field settings",id:"applying-field-settings",level:2}];function c(e){const t={a:"a",admonition:"admonition",code:"code",em:"em",h1:"h1",h2:"h2",header:"header",li:"li",p:"p",pre:"pre",ul:"ul",...(0,s.R)(),...e.components};return(0,n.jsxs)(n.Fragment,{children:[(0,n.jsx)(t.header,{children:(0,n.jsx)(t.h1,{id:"textfield",children:"TextField"})}),"\n",(0,n.jsxs)(t.p,{children:["The TextField is the most basic field that DataKit provides. It will show the value for the field, without any\nprocessing. It does ",(0,n.jsx)(t.em,{children:"not"})," allow HTML; for that you need to switch to the ",(0,n.jsx)(t.code,{children:"HtmlField"}),"."]}),"\n",(0,n.jsx)(t.pre,{children:(0,n.jsx)(t.code,{className:"language-php",children:"use DataKit\\DataViews\\Field\\TextField;\n\nTextField::create( 'field', 'Label' );\n"})}),"\n",(0,n.jsx)(t.h2,{id:"applying-field-settings",children:"Applying field settings"}),"\n",(0,n.jsxs)(t.p,{children:["Although the field does not support HTML, you can influence the way the value is displayed. The following modifiers are\navailable for a text field, on top of the ",(0,n.jsx)(t.a,{href:"/SDK/Fields/using-fields#applying-field-settings",children:"default modifiers"}),"."]}),"\n",(0,n.jsxs)(t.ul,{children:["\n",(0,n.jsxs)(t.li,{children:[(0,n.jsx)(t.code,{children:"->break()"})," Makes the content break on new lines. It does so through CSS instead of adding ",(0,n.jsx)(t.code,{children:"<br/>"})," tags."]}),"\n",(0,n.jsxs)(t.li,{children:[(0,n.jsx)(t.code,{children:"->inline()"})," Displays the content without any breaks (inverse of ",(0,n.jsx)(t.code,{children:"break()"}),")."]}),"\n",(0,n.jsxs)(t.li,{children:[(0,n.jsx)(t.code,{children:"->italic()"})," Displays the content ",(0,n.jsx)(t.em,{children:"as italic"}),"."]}),"\n",(0,n.jsxs)(t.li,{children:[(0,n.jsx)(t.code,{children:"->roman()"})," Displays the content as roman (default upright text)."]}),"\n",(0,n.jsxs)(t.li,{children:[(0,n.jsx)(t.code,{children:"->weight( string $weight = '' )"})," Displays the content according to the provided weight; e.g. ",(0,n.jsx)(t.code,{children:"bold"})," or ",(0,n.jsx)(t.code,{children:"500"}),"."]}),"\n"]}),"\n",(0,n.jsx)(t.p,{children:"Let's look at a full example:"}),"\n",(0,n.jsx)(t.pre,{children:(0,n.jsx)(t.code,{className:"language-php",children:"TextField::create( 'field', 'Label' )\n    ->weight('bold') // Adds a `font-weight:bold` style.\n    ->italic() // Adds a `text-style:italic` style.\n    ->break(); // Adds a `white-spice:pre-line` style.\n"})}),"\n",(0,n.jsx)(t.admonition,{type:"note",children:(0,n.jsxs)(t.p,{children:[(0,n.jsx)(t.code,{children:"->italic()"})," also has an optional ",(0,n.jsx)(t.code,{children:"bool $is_italic"})," parameter. So instead of ",(0,n.jsx)(t.code,{children:"->roman()"})," you can also\nuse ",(0,n.jsx)(t.code,{children:"->italic( false )"}),", if that feels more intuitive to you."]})})]})}function h(e={}){const{wrapper:t}={...(0,s.R)(),...e.components};return t?(0,n.jsx)(t,{...e,children:(0,n.jsx)(c,{...e})}):c(e)}},8453:(e,t,i)=>{i.d(t,{R:()=>d,x:()=>o});var n=i(6540);const s={},l=n.createContext(s);function d(e){const t=n.useContext(l);return n.useMemo((function(){return"function"==typeof e?e(t):{...t,...e}}),[t,e])}function o(e){let t;return t=e.disableParentContext?"function"==typeof e.components?e.components(s):e.components||s:d(e.components),n.createElement(l.Provider,{value:t},e.children)}}}]);