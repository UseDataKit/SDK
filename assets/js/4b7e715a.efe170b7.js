"use strict";(self.webpackChunkdatakit_docs=self.webpackChunkdatakit_docs||[]).push([[431],{8250:(e,t,a)=>{a.r(t),a.d(t,{assets:()=>i,contentTitle:()=>o,default:()=>l,frontMatter:()=>r,metadata:()=>n,toc:()=>u});var s=a(4848),c=a(8453);const r={title:"CsvDataSource",sidebar:"auto"},o="CsvDataSource",n={id:"SDK/Data-sources/csv-data-source",title:"CsvDataSource",description:"DataKit comes with a CsvDataSource that reads a CSV or TSV file. You can provide the separator, enclosure and escape",source:"@site/docs/SDK/Data-sources/20-csv-data-source.md",sourceDirName:"SDK/Data-sources",slug:"/SDK/Data-sources/csv-data-source",permalink:"/SDK/Data-sources/csv-data-source",draft:!1,unlisted:!1,editUrl:"https://github.com/UseDataKit/SDK/edit/main/docs/docs/SDK/Data-sources/20-csv-data-source.md",tags:[],version:"current",sidebarPosition:20,frontMatter:{title:"CsvDataSource",sidebar:"auto"},sidebar:"docs",previous:{title:"ArrayDataSource",permalink:"/SDK/Data-sources/array-data-source"},next:{title:"Introduction to fields",permalink:"/SDK/Fields/using-fields"}},i={},u=[{value:"Example usage",id:"example-usage",level:2},{value:"Using WordPress Attachments",id:"using-wordpress-attachments",level:2}];function d(e){const t={code:"code",h1:"h1",h2:"h2",header:"header",p:"p",pre:"pre",...(0,c.R)(),...e.components};return(0,s.jsxs)(s.Fragment,{children:[(0,s.jsx)(t.header,{children:(0,s.jsx)(t.h1,{id:"csvdatasource",children:"CsvDataSource"})}),"\n",(0,s.jsxs)(t.p,{children:["DataKit comes with a ",(0,s.jsx)(t.code,{children:"CsvDataSource"})," that reads a CSV or TSV file. You can provide the separator, enclosure and escape\ncharacter to suite your specific file."]}),"\n",(0,s.jsx)(t.p,{children:"The datasource assumes the first row to contain all the labels for the columns."}),"\n",(0,s.jsx)(t.h2,{id:"example-usage",children:"Example usage"}),"\n",(0,s.jsx)(t.pre,{children:(0,s.jsx)(t.code,{className:"language-php",children:"use DataKit\\DataViews\\Data\\CsvDataSource;\n\n$csv_datasource = new CsvDataSource( '<absolute_path_to_csv>' );\n$tsv_datasource = new CsvDataSource( '<absolute_path_to_tsv>', \"\\t\" ); // Separate on tabs.\n"})}),"\n",(0,s.jsx)(t.h2,{id:"using-wordpress-attachments",children:"Using WordPress Attachments"}),"\n",(0,s.jsxs)(t.p,{children:["The DataKit Plugin also provides a convenient ",(0,s.jsx)(t.code,{children:"AttachmentDataSource"})," that can create datasource's based on attachment\nID's. Instead of creating a ",(0,s.jsx)(t.code,{children:"AttachmentDataSource"}),", it has named constructors for the specific file types."]}),"\n",(0,s.jsx)(t.pre,{children:(0,s.jsx)(t.code,{className:"language-php",children:'use DataKit\\Plugin\\Data\\AttachmentDataSource;\n\n// In both these cases, the datasource will be a `CsvDataSource` with the absolute path resolved.\n$csv_datasource = AttachmentDataSource::csv( 109 );\n$tsv_datasource = AttachmentDataSource::csv( 109, "\\t" ); \n'})})]})}function l(e={}){const{wrapper:t}={...(0,c.R)(),...e.components};return t?(0,s.jsx)(t,{...e,children:(0,s.jsx)(d,{...e})}):d(e)}},8453:(e,t,a)=>{a.d(t,{R:()=>o,x:()=>n});var s=a(6540);const c={},r=s.createContext(c);function o(e){const t=s.useContext(r);return s.useMemo((function(){return"function"==typeof e?e(t):{...t,...e}}),[t,e])}function n(e){let t;return t=e.disableParentContext?"function"==typeof e.components?e.components(c):e.components||c:o(e.components),s.createElement(r.Provider,{value:t},e.children)}}}]);