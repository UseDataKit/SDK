export default function Image( { name, item, context } ) {
    const alt = context?.alt || '';
    const height = context?.height || '';
    const width = context?.width || '';
    const className = context?.class || '';

    return <div>
        <img className={className} src={item[ name ]} width={width || ''} height={height || ''} alt={alt}/>
    </div>;
}
