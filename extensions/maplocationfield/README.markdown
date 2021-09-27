# Map Location Field

~~Ugly~~ Slightly dirty hack from Nick Dunn’s original version.

+ Removed Tabs
+ Removed sensor option
+ Possibility to add API-Key in field settings
+ OSM Map included as Layer
+ Localisation

## Installation

1. Upload the `/maplocationfield` folder in this archive to your Symphony `/extensions` folder
2. Enable it by selecting the "Field: Map Location", choose Enable from the with-selected menu, then click Apply
3. The field will be available in the list when creating a Section
4. You will need a [Google Maps API Key](https://developers.google.com/maps/documentation/javascript/get-api-key) in order to use geolocations services. If you have that, paste it in the appropriate field in the field settings.

## Configuration

When adding this field to a section, the following options are available to you:

* **Default Marker Location** is the address of a default marker. Enter any address/ZIP to be geocoded
* **Default Zoom Level** is the initial zoom level of the map

The field works in both Main Content and Sidebar columns, collapsing to a smaller viewport if required.

## Usage

When creating a new entry, drag the red marker on the map to change location. To tweak the latitude/longitude use the corresponding fields below the map. The address input also allows you to enter an address to be geocoded and placed on the map.

![Example field](https://cloud.githubusercontent.com/assets/446874/20055986/31cd4f4a-a4e4-11e6-934c-4a774c85d418.png)

## Data Source Filtering

The field provides a single syntax for radius-based searches. Use the following as a DS filter:

	within DISTANCE UNIT of ORIGIN

* `DISTANCE` is an integer
* `UNIT` is the distance unit: `km`, `mile` or `miles`
* `ORIGIN` is the centre of the radius. Accepts either a latitude/longitude pair or an address

Examples:

	within 20 km of 10.545,-103.1
	within 1km of 1 West Street, Burleigh Heads, Australia
	within 500 miles of London

To make the filters dynamic, use the parameter syntax like any other filter. For example using querystring parameters:

	within {$url-distance} {$url-unit} of {$url-origin}

Attached to a page invoked as:

	/?distance=30&unit=km&origin=London,England

## Data Source XML result
The XML output of the field looks like this:

	<location latitude="51.6614" longitude="-0.40042">
		<map zoom="15" centre="51.6614,-0.40042" />
	</location>

The first two attributes are the latitude/longitude of the marker on the map. The `<map>` element contains any information you need to rebuild the Google Map on the frontend of your website: its zoom level, centre-point and your API key.

If you are filtering using the Map Location Field using a "within" filter then you will see an additional `<distance>` element:

	<location latitude="51.6614" longitude="-0.40042">
		<map zoom="15" centre="51.6614,-0.40042" />
		<distance from="51.6245572,-0.4674079" distance="3.8" unit="miles" />
	</location>

The `from` attribute is the latitude/longitude resolved from the DS filter (the origin), the `unit` shows either "km" or "miles" depending on what you use in your filter, and `distance` is the distance between your map marker and the origin.