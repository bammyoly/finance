import ProfileController from './ProfileController'
import OrderController from './OrderController'
import Settings from './Settings'


const Controllers = {
    ProfileController: Object.assign(ProfileController, ProfileController),
    OrderController: Object.assign(OrderController, OrderController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers