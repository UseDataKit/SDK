"use strict";(self.webpackChunkmy_website=self.webpackChunkmy_website||[]).push([[148],{2186:(e,i,l)=>{l.r(i),l.d(i,{assets:()=>d,contentTitle:()=>a,default:()=>h,frontMatter:()=>t,metadata:()=>r,toc:()=>o});var s=l(4848),n=l(8453);const t={},a="EnumField",r={id:"Fields/enum-field",title:"EnumField",description:"An EnumField is a field type that contains a fixed set of values (an enumeration). For every value the field provides",source:"@site/docs/Fields/20-enum-field.md",sourceDirName:"Fields",slug:"/Fields/enum-field",permalink:"/Fields/enum-field",draft:!1,unlisted:!1,editUrl:"https://github.com/UseDataKit/SDK/edit/main/docs/Fields/20-enum-field.md",tags:[],version:"current",sidebarPosition:20,frontMatter:{},sidebar:"docs",previous:{title:"DateTimeField",permalink:"/Fields/datetime-field"},next:{title:"ImageField",permalink:"/Fields/image-field"}},d={},o=[{value:"Applying field settings",id:"applying-field-settings",level:2},{value:"Filtering",id:"filtering",level:2},{value:"Operators",id:"operators",level:3}];function c(e){const i={a:"a",code:"code",h1:"h1",h2:"h2",h3:"h3",header:"header",li:"li",p:"p",pre:"pre",ul:"ul",...(0,n.R)(),...e.components};return(0,s.jsxs)(s.Fragment,{children:[(0,s.jsx)(i.header,{children:(0,s.jsx)(i.h1,{id:"enumfield",children:"EnumField"})}),"\n",(0,s.jsxs)(i.p,{children:["An ",(0,s.jsx)(i.code,{children:"EnumField"})," is a field type that contains a fixed set of values (an enumeration). For every value the field provides\na companion label. This label is what is shown on the field."]}),"\n",(0,s.jsxs)(i.p,{children:["Because the field requires a set of values, the ",(0,s.jsx)(i.code,{children:"EnumField::create()"})," method also requires an additional ",(0,s.jsx)(i.code,{children:"$elements"}),"\nparameter. This parameter should receive a key => value array where the key is the value, and the value is the label."]}),"\n",(0,s.jsx)(i.pre,{children:(0,s.jsx)(i.code,{className:"language-php",children:"use DataKit\\DataViews\\Field\\EnumField;\n\nEnumField::create( 'status', 'Status', [\n    'active' => 'Active',\n    'disabled' => 'Disabled',\n]);\n"})}),"\n",(0,s.jsxs)(i.p,{children:["In this example, the field will show the label ",(0,s.jsx)(i.code,{children:"Active"})," on the view, for any dataset that contains the value ",(0,s.jsx)(i.code,{children:"active"})," on\nthe ",(0,s.jsx)(i.code,{children:"status"})," key."]}),"\n",(0,s.jsx)(i.h2,{id:"applying-field-settings",children:"Applying field settings"}),"\n",(0,s.jsxs)(i.p,{children:["The ",(0,s.jsx)(i.code,{children:"EnumField"})," has a few other settings modifiers on top of\nthe ",(0,s.jsx)(i.a,{href:"/Fields/using-fields#applying-field-settings",children:"default modifiers"}),"."]}),"\n",(0,s.jsxs)(i.ul,{children:["\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"->filterable_by( Operator ... $operators )"})," Makes the field filterable. ",(0,s.jsx)(i.a,{href:"#operators",children:"Read more about operators"})]}),"\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"->primary()"})," Makes the field filterable as a primary filter, which is always\nvisible. ",(0,s.jsx)(i.a,{href:"#filtering",children:"Read more about filtering"})]}),"\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"->secondary()"}),' (default) Makes the field filterable as a secondary filter, which is only shown on the "Add Filter"\ncomponent.']}),"\n"]}),"\n",(0,s.jsx)(i.h2,{id:"filtering",children:"Filtering"}),"\n",(0,s.jsxs)(i.p,{children:["The ",(0,s.jsx)(i.code,{children:"EnumField"})," is a special field that can be filtered. These filters are applied by the datasource to filter the\nresults based on the selected options. There is a limited set of ",(0,s.jsx)(i.a,{href:"#operators",children:(0,s.jsx)(i.code,{children:"Operators"})})," available."]}),"\n",(0,s.jsxs)(i.p,{children:["Once an EnumField is filterable, it can be either a ",(0,s.jsx)(i.code,{children:"primary"})," or a ",(0,s.jsx)(i.code,{children:"secondary"}),' filter. All primary filters are always\nvisible on the DataView UI, while secondary filters are hidden behind a "add filter" component.']}),"\n",(0,s.jsxs)(i.p,{children:["For all filtering options, the ",(0,s.jsx)(i.code,{children:"elements"})," are used as the values to be filtered on."]}),"\n",(0,s.jsx)(i.h3,{id:"operators",children:"Operators"}),"\n",(0,s.jsxs)(i.p,{children:["An ",(0,s.jsx)(i.code,{children:"EnumField"})," can be filtered with a limited set of operators."]}),"\n",(0,s.jsx)(i.p,{children:"There are two operators that are used for a single filtering value (you can select one value)."}),"\n",(0,s.jsxs)(i.ul,{children:["\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"Operator::is()"})," The field value is EQUAL TO the selected value."]}),"\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"Operator::isNot()"})," The field value is NOT EQUAL TO the selected value."]}),"\n"]}),"\n",(0,s.jsx)(i.p,{children:"For multiple filter values (you can select multiple values), there are four operators available:"}),"\n",(0,s.jsxs)(i.ul,{children:["\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"Operator::isAny()"})," The value is at least one of the selected values (e.g. value1 ",(0,s.jsx)(i.code,{children:"OR"})," value2)"]}),"\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"Operator::isNone()"})," The value is NOT present in the selected values (e.g. is ",(0,s.jsx)(i.code,{children:"NOT"})," value1 ",(0,s.jsx)(i.code,{children:"AND NOT"})," value2)"]}),"\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"Operator::isAll()"})," The field contains ALL the selected options (e.g. has value1 ",(0,s.jsx)(i.code,{children:"AND"})," value2)"]}),"\n",(0,s.jsxs)(i.li,{children:[(0,s.jsx)(i.code,{children:"Operator::isNotAll()"})," The field does NOT contain ALL the selected options (it can have some or none, but not all)"]}),"\n"]}),"\n",(0,s.jsx)(i.pre,{children:(0,s.jsx)(i.code,{className:"language-php",children:"use DataKit\\DataViews\\DataView\\Operator;\n\n$status = EnumField::create( 'status', 'Status', [\n    'active' => 'Active',\n    'disabled' => 'Disabled',\n])->filterable_by( Operator::isAny(), Operator::isNone() );\n"})})]})}function h(e={}){const{wrapper:i}={...(0,n.R)(),...e.components};return i?(0,s.jsx)(i,{...e,children:(0,s.jsx)(c,{...e})}):c(e)}},8453:(e,i,l)=>{l.d(i,{R:()=>a,x:()=>r});var s=l(6540);const n={},t=s.createContext(n);function a(e){const i=s.useContext(t);return s.useMemo((function(){return"function"==typeof e?e(i):{...i,...e}}),[i,e])}function r(e){let i;return i=e.disableParentContext?"function"==typeof e.components?e.components(n):e.components||n:a(e.components),s.createElement(t.Provider,{value:i},e.children)}}}]);