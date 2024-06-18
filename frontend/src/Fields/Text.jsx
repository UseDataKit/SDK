export default function Text( { name, item } ) {
    return <div>{item[ name ] || ''}</div>;
}
