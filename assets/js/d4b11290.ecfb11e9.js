"use strict";(self.webpackChunkdatakit_docs=self.webpackChunkdatakit_docs||[]).push([[610],{3647:(e,t,i)=>{i.r(t),i.d(t,{assets:()=>r,contentTitle:()=>s,default:()=>m,frontMatter:()=>a,metadata:()=>l,toc:()=>o});var n=i(4848),d=i(8453);const a={},s="DateTimeField",l={id:"Fields/datetime-field",title:"DateTimeField",description:"The DateTimeField renders a datetime value according to a provided format. You can provide the format the value is",source:"@site/docs/Fields/18-datetime-field.md",sourceDirName:"Fields",slug:"/Fields/datetime-field",permalink:"/Fields/datetime-field",draft:!1,unlisted:!1,editUrl:"https://github.com/UseDataKit/SDK/edit/main/docs/docs/Fields/18-datetime-field.md",tags:[],version:"current",sidebarPosition:18,frontMatter:{},sidebar:"docs",previous:{title:"HtmlField",permalink:"/Fields/html-field"},next:{title:"EnumField",permalink:"/Fields/enum-field"}},r={},o=[{value:"Applying field settings",id:"applying-field-settings",level:2}];function c(e){const t={a:"a",code:"code",h1:"h1",h2:"h2",header:"header",li:"li",p:"p",pre:"pre",ul:"ul",...(0,d.R)(),...e.components};return(0,n.jsxs)(n.Fragment,{children:[(0,n.jsx)(t.header,{children:(0,n.jsx)(t.h1,{id:"datetimefield",children:"DateTimeField"})}),"\n",(0,n.jsxs)(t.p,{children:["The ",(0,n.jsx)(t.code,{children:"DateTimeField"})," renders a datetime value according to a provided format. You can provide the format the value is\nread, and the format the value should be displayed in. For both you can also provide the required time zones."]}),"\n",(0,n.jsx)(t.h2,{id:"applying-field-settings",children:"Applying field settings"}),"\n",(0,n.jsxs)(t.p,{children:["The ",(0,n.jsx)(t.code,{children:"DateTimeField"})," has a few other settings modifiers on top of\nthe ",(0,n.jsx)(t.a,{href:"/Fields/using-fields#applying-field-settings",children:"default modifiers"}),"."]}),"\n",(0,n.jsxs)(t.ul,{children:["\n",(0,n.jsxs)(t.li,{children:[(0,n.jsx)(t.code,{children:"->from_format( string $format, ?DateTimeZone $timezone = null)"})," Makes sure to interpret the value correctly."]}),"\n",(0,n.jsxs)(t.li,{children:[(0,n.jsx)(t.code,{children:"->to_format( string $format, ?DateTimeZone $timezone = null)"})," Sets the format to display the datetime in."]}),"\n"]}),"\n",(0,n.jsx)(t.p,{children:"Here is a full example:"}),"\n",(0,n.jsx)(t.pre,{children:(0,n.jsx)(t.code,{className:"language-php",children:"use DataKit\\DataViews\\Field\\DateTimeField;\n\n// Assume `date_created` has a value of \"2024-07-16 15:57:45\" \nDateTimeField::create( 'date_created', 'Created on' )\n    ->from_format( 'Y-m-d H:i:s', new DateTimeZone( 'UTC' ) ) // The value is stored in UTC.\n    ->to_format( 'D, d M Y H:i', new DateTimeZone( 'Europe/Amsterdam' ) ); // Will be displayed as: \"Tue, 16 Jul 2024 17:57\" (UTC+2). \n"})})]})}function m(e={}){const{wrapper:t}={...(0,d.R)(),...e.components};return t?(0,n.jsx)(t,{...e,children:(0,n.jsx)(c,{...e})}):c(e)}},8453:(e,t,i)=>{i.d(t,{R:()=>s,x:()=>l});var n=i(6540);const d={},a=n.createContext(d);function s(e){const t=n.useContext(a);return n.useMemo((function(){return"function"==typeof e?e(t):{...t,...e}}),[t,e])}function l(e){let t;return t=e.disableParentContext?"function"==typeof e.components?e.components(d):e.components||d:s(e.components),n.createElement(a.Provider,{value:t},e.children)}}}]);