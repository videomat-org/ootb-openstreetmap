import getBounds from '../Helpers/getBounds';
// noinspection NpmUsedModulesInstalled
import {Button} from '@wordpress/components';

export default function SearchResults({props}) {
	const {
		attributes: {
			markers,
			searchResults,
			mapObj,
			inputRef,
		},
		setAttributes,
	} = props;
	const addMarker = (e) => {
		const index = e.target.getAttribute('data-index');
		const {display_name, lon, lat} = searchResults[index];
		const newMarker = {
			lat: lat.toString(),
			lng: lon.toString(),
			text: `<p>${display_name}</p>`,
		};
		setAttributes({
			markers: [
				...markers,
				newMarker
			],
			keywords: '',
			searchResults: [],
		});
		setAttributes({shouldUpdateBounds: true});
		getBounds(props, newMarker, mapObj);
		if (inputRef) {
			inputRef.target.focus();
		}
	}

	const resultsList = () => {
		return searchResults.map((item, index) => {
			const {display_name} = item;
			return (
				<Button
					key={index}
					autoFocus={(index === 0)}
					onClick={addMarker}
					data-index={index}
				>{display_name}</Button>
			);
		});
	}
	return searchResults && searchResults.length ? (
		<div className="ootb-openstreetmap--search-results">
			{resultsList()}
		</div>
	) : null;
}
